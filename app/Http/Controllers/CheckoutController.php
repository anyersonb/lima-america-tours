<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayPalCaptureRequest;
use App\Http\Requests\ProcessPaymentRequest;
use App\Mail\AccountCredentials;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Customer;
use App\Services\AbandonedCartService;
use App\Services\BookingNotifier;
use App\Services\BookingStateMachine;
use App\Services\CartService;
use App\Services\PaymentService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PaymentService $payment,
        private readonly PayPalService $paypal,
        private readonly BookingNotifier $notifier,
        private readonly AbandonedCartService $abandoned,
        private readonly BookingStateMachine $stateMachine,
    ) {}

    /**
     * Renders the Culqi payment step (resources/views/checkout/payment.blade.php)
     * when the cart has items. This is the "pay now with card" entry point —
     * the 3-step wizard (checkout.blade.php, still the main checkout UI) links
     * here for the standalone Culqi form; it also posts straight to
     * checkout.process with a culqi_token once Culqi.js tokenises the card.
     */
    public function showPaymentForm(Request $request): View|RedirectResponse
    {
        $locale = app()->getLocale();

        try {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                return redirect()
                    ->route('cart.index', ['locale' => $locale])
                    ->with('error', __('cart.empty_checkout_redirect'));
            }

            $total = $this->cart->total();

            return view('checkout.payment', [
                'items' => $items,
                'subtotal' => $this->cart->subtotal(),
                'discount' => $this->cart->couponDiscount(),
                'couponCode' => $this->cart->couponCode(),
                'total' => $total,
                'total_centavos' => (int) round($total * 100),
                'public_key' => config('services.culqi.public_key'),
            ]);

        } catch (\Throwable $e) {
            Log::error('checkout.show_payment_form.error', ['message' => $e->getMessage()]);

            return redirect()
                ->route('cart.index', ['locale' => $locale])
                ->with('error', 'Ocurrió un error al cargar el formulario de pago.');
        }
    }

    /**
     * Single entry point for the checkout form submission. Branches on
     * whether a Culqi token was tokenised client-side:
     *  - culqi_token present  -> charges it now (chargeWithCulqi).
     *  - culqi_token absent   -> "book now, pay later" hold, closed via the
     *    WhatsApp/email buttons already in the wizard (unchanged behaviour).
     *
     * The PayPal "pay now" flow is handled separately by paypalCreateOrder +
     * paypalCaptureOrder (JS SDK popup, not a classic form submit).
     */
    public function processPayment(ProcessPaymentRequest $request): RedirectResponse
    {
        $locale = app()->getLocale();

        try {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                return redirect()
                    ->route('cart.index', ['locale' => $locale])
                    ->with('error', __('cart.empty_checkout_redirect'));
            }

            $validated = $request->validated();

            $customer = [
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'travel_date' => $validated['travel_date'],
                'pickup_point' => $request->input('pickup_point'),
                'pickup_detail' => $request->input('pickup_detail'),
            ];

            // Defense-in-depth: re-verify blocked dates for every cart item
            if ($this->anyItemBlocked($items, $validated['travel_date'])) {
                return redirect()
                    ->route('cart.index', ['locale' => $locale])
                    ->with('error', __('booking.date_blocked'));
            }

            if ($request->filled('culqi_token')) {
                return $this->chargeWithCulqi($request, $customer, (string) $validated['culqi_token'], $locale);
            }

            // "Book now, pay later" — creates a pending_payment hold; the
            // wizard's WhatsApp/email buttons close it outside the system.
            $bookings = $this->finalizeBookings($customer, 'pay_later', null, BookingStateMachine::PENDING_PAYMENT);

            $request->session()->put('last_bookings', $bookings->toArray());

            return redirect()->route('checkout.thanks', ['locale' => $locale]);

        } catch (\Throwable $e) {
            Log::error('checkout.process_payment.error', [
                'message' => $e->getMessage(),
                'email' => $request->input('customer_email'),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No pudimos procesar la reserva. Por favor inténtalo de nuevo o contáctanos.');
        }
    }

    /**
     * Culqi "pay now" — docs/pagos/PLAN-PASARELAS.md §3.2.
     *
     * 1. Locks on the session so a double form-submit (double click) can't
     *    create two charges/booking sets for the same cart.
     * 2. Creates the Bookings in `pending_payment` FIRST (hold), so the
     *    payment_reference can be fixed to the charge id as soon as it comes
     *    back — this is what lets WebhookController::culqi() reconcile the
     *    same booking asynchronously if the synchronous response is lost.
     * 3. Charges via PaymentService::createCharge(); success -> PAID
     *    transition + confirmation email + cart clear. Failure -> FAILED
     *    transition, hold stays alive (expires_at untouched) so the customer
     *    can retry with another card without duplicating bookings.
     */
    private function chargeWithCulqi(Request $request, array $customer, string $token, string $locale): RedirectResponse
    {
        $lock = Cache::lock('checkout:culqi-charge:'.session()->getId(), 20);

        if (! $lock->get()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Tu pago ya se está procesando. Espera unos segundos.');
        }

        try {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                return redirect()
                    ->route('cart.index', ['locale' => $locale])
                    ->with('error', __('cart.empty_checkout_redirect'));
            }

            $bookings = $this->createHeldBookings($customer, 'culqi');

            $amountCents = (int) round($this->cart->total() * 100);

            try {
                $charge = $this->payment->createCharge([
                    'amount' => $amountCents,
                    'currency' => config('services.culqi.currency', 'USD'),
                    'email' => $customer['customer_email'],
                    'source_id' => $token,
                    'metadata' => [
                        'booking_references' => $bookings->pluck('reference')->implode(','),
                    ],
                ]);
            } catch (\RuntimeException $e) {
                foreach ($bookings as $booking) {
                    $this->stateMachine->transition($booking, BookingStateMachine::FAILED);
                }

                Log::warning('checkout.culqi_charge.rejected', [
                    'message' => $e->getMessage(),
                    'bookings' => $bookings->pluck('reference')->all(),
                ]);

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'El pago con tarjeta no pudo procesarse. Verifica los datos o inténtalo con otro método.');
            }

            $chargeId = $charge['id'] ?? null;

            foreach ($bookings as $booking) {
                $this->stateMachine->transition($booking, BookingStateMachine::PAID, [
                    'payment_reference' => $chargeId,
                ]);
            }

            $this->runPostBookingSideEffects($bookings, true, $customer['customer_email']);

            $request->session()->put('last_bookings', $bookings->toArray());

            return redirect()->route('checkout.thanks', ['locale' => $locale]);

        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Creates a PayPal order for the current cart total.
     * Returns JSON with the PayPal order ID for the JS SDK.
     */
    public function paypalCreateOrder(Request $request, string $locale): JsonResponse
    {
        try {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                return response()->json(['error' => 'El carrito está vacío.'], 422);
            }

            // Amount always calculated server-side — never trust the client
            $total = $this->cart->total();

            $order = $this->paypal->createOrder($total, 'USD', [
                'locale' => $locale,
            ]);

            return response()->json(['id' => $order['id']]);

        } catch (\Throwable $e) {
            Log::error('checkout.paypal_create_order.error', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'No se pudo iniciar el pago. Inténtalo de nuevo.'], 500);
        }
    }

    /**
     * Captures a PayPal order approved by the buyer.
     * On success, finalizes the bookings and returns a redirect URL.
     *
     * Idempotent against double-submits / a race with a lost response
     * (docs/pagos/PLAN-PASARELAS.md §5.3, §6):
     *  - A per-orderID lock serializes concurrent capture attempts.
     *  - If PayPal reports the order as already captured (double click, or
     *    the first response never reached the browser), the existing capture
     *    is fetched via getOrder() instead of erroring.
     *  - If a Booking already carries that capture id as payment_reference
     *    (finalized by a previous request, or — once the webhook can create
     *    bookings on its own — reconciled asynchronously), no new Booking is
     *    created; the same "thanks" redirect is returned.
     */
    public function paypalCaptureOrder(PayPalCaptureRequest $request, string $locale): JsonResponse
    {
        $validated = $request->validated();
        $orderId = $validated['orderID'];

        $lock = Cache::lock('checkout:paypal-capture:'.$orderId, 15);

        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Este pago ya se está procesando. Espera unos segundos.',
            ], 409);
        }

        try {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'El carrito está vacío.'], 422);
            }

            if ($this->anyItemBlocked($items, $validated['travel_date'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('booking.date_blocked'),
                ], 422);
            }

            try {
                $captureResponse = $this->paypal->captureOrder($orderId);
            } catch (\RuntimeException $e) {
                if (! Str::contains($e->getMessage(), 'ALREADY_CAPTURED')) {
                    throw $e;
                }

                // Double click / lost response: PayPal already captured this
                // order for us — fetch it instead of treating this as a failure.
                Log::info('checkout.paypal_capture.already_captured_retry', ['order_id' => $orderId]);
                $captureResponse = $this->paypal->getOrder($orderId);
            }

            $captureId = $this->paypal->captureId($captureResponse);

            if ($captureId && Booking::where('payment_method', 'paypal')->where('payment_reference', $captureId)->exists()) {
                Log::info('checkout.paypal_capture.idempotent_skip', ['capture_id' => $captureId]);

                return response()->json([
                    'success' => true,
                    'redirect' => route('checkout.thanks', ['locale' => $locale]),
                ]);
            }

            $customer = [
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'travel_date' => $validated['travel_date'],
                'pickup_point' => $validated['pickup_point'] ?? null,
                'pickup_detail' => $validated['pickup_detail'] ?? null,
            ];

            $bookings = $this->finalizeBookings($customer, 'paypal', $captureId, BookingStateMachine::PAID);

            $request->session()->put('last_bookings', $bookings->toArray());

            return response()->json([
                'success' => true,
                'redirect' => route('checkout.thanks', ['locale' => $locale]),
            ]);

        } catch (\Throwable $e) {
            Log::error('checkout.paypal_capture_order.error', [
                'message' => $e->getMessage(),
                'order_id' => $orderId,
                'email' => $validated['customer_email'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El pago no pudo completarse. Por favor inténtalo de nuevo o contáctanos.',
            ], 500);
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Show the thank-you page after a successful payment.
     */
    public function thanks(Request $request): View
    {
        $bookings = collect($request->session()->get('last_bookings', []));

        return view('checkout.thanks', [
            'bookings' => $bookings,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Re-verifies every cart item against blocked dates for the chosen
     * travel date. Shared by processPayment() and paypalCaptureOrder() so
     * both gateways apply the exact same defense-in-depth check.
     */
    private function anyItemBlocked(Collection $items, string $travelDate): bool
    {
        foreach ($items as $item) {
            if (BlockedDate::isBlocked($travelDate, $item['tour_id'] ?? null)) {
                Log::info('checkout.blocked_date_rejected', [
                    'travel_date' => $travelDate,
                    'tour_id' => $item['tour_id'] ?? null,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Builds the attributes common to every Booking row created from the
     * cart, regardless of gateway or target state. $stateColumns comes from
     * BookingStateMachine::columns() (or a manual transition's extras).
     */
    private function baseBookingAttrs(
        array $item,
        array $customer,
        string $method,
        ?string $paymentReference,
        string $locale,
        int $customerId,
        bool $hasPickupColumns,
    ): array {
        $attrs = [
            'customer_id' => $customerId,
            'tour_id' => $item['tour_id'],
            'tour_title_snapshot' => $item['title_snapshot'],
            'customer_name' => $customer['customer_name'],
            'customer_email' => $customer['customer_email'],
            'customer_phone' => $customer['customer_phone'],
            'travel_date' => $customer['travel_date'],
            'adults' => $item['adults'],
            'children' => $item['children'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['subtotal'],
            'currency' => 'USD',
            'payment_method' => $method,
            'payment_reference' => $paymentReference,
            'locale' => $locale,
        ];

        if ($hasPickupColumns) {
            $attrs['pickup_point'] = $customer['pickup_point'] ?? null;
            $attrs['pickup_detail'] = $customer['pickup_detail'] ?? null;
        }

        return $attrs;
    }

    /**
     * Creates one Booking per cart item directly in the target state, sends
     * confirmation emails (when paid), closes the abandoned-cart record and
     * clears the cart. Used by the "pay later" hold (PENDING_PAYMENT) and by
     * the PayPal capture flow (PAID) — both gateways share this method.
     *
     * @param  array  $customer  Keys: customer_name, customer_email,
     *                           customer_phone, travel_date,
     *                           pickup_point, pickup_detail
     * @param  string  $method  'paypal' | 'pay_later'
     * @param  string|null  $paymentReference  PayPal capture ID (null for pay_later)
     * @param  string  $state  A BookingStateMachine::* constant
     * @return Collection<Booking>
     */
    private function finalizeBookings(
        array $customer,
        string $method,
        ?string $paymentReference,
        string $state,
    ): Collection {
        $locale = app()->getLocale();
        $items = $this->cart->items();
        $hasPickupColumns = Schema::hasColumn('bookings', 'pickup_point');
        $customerId = $this->resolveCustomerId($customer, $locale);
        $stateColumns = BookingStateMachine::columns($state);

        $bookings = $items->map(function (array $item) use (
            $customer, $method, $paymentReference, $stateColumns, $locale, $hasPickupColumns, $customerId
        ): Booking {
            $attrs = array_merge(
                $this->baseBookingAttrs($item, $customer, $method, $paymentReference, $locale, $customerId, $hasPickupColumns),
                $stateColumns,
            );

            return Booking::create($attrs);
        });

        Log::info('checkout.finalize_bookings', [
            'method' => $method,
            'state' => $state,
            'reference' => $paymentReference,
            'email' => $customer['customer_email'],
            'customer_id' => $customerId,
            'bookings' => $bookings->pluck('reference')->all(),
        ]);

        $this->runPostBookingSideEffects($bookings, $state === BookingStateMachine::PAID, $customer['customer_email']);

        return $bookings;
    }

    /**
     * Creates one Booking per cart item as a PENDING_PAYMENT hold, WITHOUT
     * sending emails, closing the abandoned cart or clearing the cart —
     * those only happen once the Culqi charge actually succeeds
     * (chargeWithCulqi calls runPostBookingSideEffects itself after the
     * PAID transition). This is the "reserve cupo before charging" step of
     * docs/pagos/PLAN-PASARELAS.md §3.2.
     *
     * @return Collection<Booking>
     */
    private function createHeldBookings(array $customer, string $method): Collection
    {
        $locale = app()->getLocale();
        $items = $this->cart->items();
        $hasPickupColumns = Schema::hasColumn('bookings', 'pickup_point');
        $customerId = $this->resolveCustomerId($customer, $locale);

        $stateColumns = array_merge(
            BookingStateMachine::columns(BookingStateMachine::PENDING_PAYMENT),
            ['expires_at' => now()->addMinutes(BookingStateMachine::HOLD_MINUTES)],
        );

        $bookings = $items->map(function (array $item) use (
            $customer, $method, $stateColumns, $locale, $hasPickupColumns, $customerId
        ): Booking {
            $attrs = array_merge(
                $this->baseBookingAttrs($item, $customer, $method, null, $locale, $customerId, $hasPickupColumns),
                $stateColumns,
            );

            return Booking::create($attrs);
        });

        Log::info('checkout.held_bookings_created', [
            'method' => $method,
            'email' => $customer['customer_email'],
            'bookings' => $bookings->pluck('reference')->all(),
        ]);

        return $bookings;
    }

    /**
     * Sends the customer/admin notifications, closes the matching abandoned
     * cart record and clears the session cart. Shared tail of both
     * finalizeBookings() (pay_later, PayPal) and the Culqi success path in
     * chargeWithCulqi() — this is intentionally NOT run on a Culqi failure,
     * so the cart/hold stay intact for a retry.
     */
    private function runPostBookingSideEffects(Collection $bookings, bool $paid, string $customerEmail): void
    {
        $this->notifier->send($bookings, $paid, $customerEmail);
        $this->abandoned->markConverted(session()->getId(), $customerEmail);
        $this->cart->clear();
    }

    /**
     * Returns the customer_id to attach to new bookings.
     * - Logged-in customers: use their existing id.
     * - Existing email (no session): reuse without sending any email.
     * - New email: create an account with a generated temporary password
     *   and send the credentials via email (AccountCredentials mailable).
     */
    private function resolveCustomerId(array $customer, string $locale): int
    {
        $loggedIn = auth('customer')->user();

        if ($loggedIn) {
            return $loggedIn->id;
        }

        $existing = Customer::where('email', $customer['customer_email'])->first();

        if ($existing) {
            return $existing->id;
        }

        // Generate a readable temporary password (10 chars, no symbols, no ambiguous chars).
        // Str::password() is available since Laravel 10.x.
        $plain = Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);

        // Create the new guest customer — the 'hashed' cast on Customer::$password
        // automatically bcrypts the plain string on assignment.
        $guestCustomer = Customer::create([
            'name' => $customer['customer_name'],
            'email' => $customer['customer_email'],
            'phone' => $customer['customer_phone'] ?? null,
            'locale' => $locale,
            'password' => $plain,
        ]);

        // Send credentials email. Failure is non-fatal: log a warning and continue.
        try {
            Mail::to($guestCustomer->email)
                ->send(new AccountCredentials($guestCustomer, $plain, $locale));
        } catch (\Throwable $ex) {
            Log::warning('checkout.guest_credentials_email.failed', [
                'email' => $guestCustomer->email,
                'message' => $ex->getMessage(),
            ]);
        }

        return $guestCustomer->id;
    }
}
