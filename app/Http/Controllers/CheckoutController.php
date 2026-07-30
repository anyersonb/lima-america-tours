<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Mail\AccountCredentials;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tour;
use App\Services\AbandonedCartService;
use App\Services\BookingNotifier;
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
    ) {}

    /**
     * Renders the standalone Culqi payment step (resources/views/checkout/payment.blade.php).
     * This is the "pay now with card" entry point that posts to checkout.process
     * with a culqi_token once Culqi.js tokenises the card, or with
     * payment_timing=later for the "book now, pay later" hold.
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
     * Single entry point for the checkout form submission.
     *
     *  - payment_timing=now  -> charges the cart total via Culqi in the site
     *    currency (Money::site(), hoy USD), in cents, using the culqi_token
     *    tokenised client-side (chargeWithCulqi).
     *  - payment_timing=later -> creates a pending booking, sends emails,
     *    clears the cart and redirects to the thanks page (unchanged).
     *
     * PayPal está activo desde que el cliente definió la moneda en USD
     * (2026-07-29) — ver docs/pagos/PLAN-PASARELAS.md §13.
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

            // CRO #1: el sitio cobra en UNA moneda (Money::site(), hoy USD).
            // Si un tour del carrito viene etiquetado en otra, abortamos ANTES
            // de calcular/cobrar nada, en vez de sumar soles y dólares como si
            // fueran el mismo número (ese fue el sobrecobro de ~3.7× de la
            // auditoría). Se relajará cuando haya cobro multimoneda de verdad.
            if (! $this->cart->isSiteCurrencyOnly()) {
                Log::warning('checkout.process_payment: currency mismatch in cart, aborting', [
                    'site_currency' => \App\Support\Money::site(),
                    'currencies' => $this->cart->currencies()->all(),
                    'tour_ids' => $items->pluck('tour_id')->all(),
                    'email' => $request->input('customer_email'),
                ]);

                return redirect()
                    ->route('cart.index', ['locale' => $locale])
                    ->with('error', 'No pudimos procesar tu reserva: uno de los tours de tu carrito aún no está habilitado para cobro en línea. Contáctanos por WhatsApp para completarla manualmente.');
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
            $travelDate = $validated['travel_date'];
            foreach ($items as $item) {
                if (BlockedDate::isBlocked($travelDate, $item['tour_id'] ?? null)) {
                    Log::info('checkout.process_payment: blocked date rejected', [
                        'travel_date' => $travelDate,
                        'tour_id' => $item['tour_id'] ?? null,
                    ]);

                    return redirect()
                        ->route('cart.index', ['locale' => $locale])
                        ->with('error', __('booking.date_blocked'));
                }
            }

            if ($validated['payment_timing'] === 'now') {
                return $this->chargeWithCulqi($request, $customer, (string) $validated['culqi_token'], $locale);
            }

            $bookings = $this->finalizeBookings($customer, 'pay_later', null, false);

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
     * Culqi "pay now" — charges the cart total in the site currency.
     *
     * 1. Locks on the session so a double form-submit (double click) can't
     *    fire two charges for the same cart.
     * 2. Creates the Bookings first, in the default pending/pending state
     *    (a hold), so a booking always exists to attach the charge result
     *    to — including on failure, per docs/qa: the customer/tour-side
     *    always sees *some* record of the attempt, and can retry without
     *    losing their cart contents (kept intact until the charge succeeds).
     * 3. Charges via PaymentService::createCharge(), which already runs the
     *    guarda anti-cobro-real (PaymentGuard) before touching the network.
     *    Success -> booking marked paid/confirmed, confirmation email sent,
     *    cart cleared. Failure (rejected card OR RealChargeBlockedException)
     *    -> booking marked payment_status=failed, cart is left untouched so
     *    the customer can retry with another card; NEVER a 500.
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

            $bookings = $this->finalizeBookings($customer, 'culqi', null, false, notify: false);

            $amountCents = (int) round($this->cart->total() * 100);

            try {
                $charge = $this->payment->createCharge([
                    'amount' => $amountCents,
                    'currency' => \App\Support\Money::site(),
                    'email' => $customer['customer_email'],
                    'source_id' => $token,
                    'metadata' => [
                        'booking_references' => $bookings->pluck('reference')->implode(','),
                    ],
                ]);
            } catch (\RuntimeException $e) {
                // Covers both a real Culqi rejection and RealChargeBlockedException
                // (the guarda anti-cobro-real) — either way, no real money moved.
                Booking::whereIn('id', $bookings->pluck('id'))->update(['payment_status' => 'failed']);

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

            Booking::whereIn('id', $bookings->pluck('id'))->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'payment_reference' => $chargeId,
            ]);

            // Reload so the notifier/email/session snapshot carry the final state.
            $bookings = Booking::whereIn('id', $bookings->pluck('id'))->get();

            $this->notifier->send($bookings, true, $customer['customer_email']);
            $this->abandoned->markConverted(session()->getId(), $customer['customer_email']);
            $this->cart->clear();

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

            // Moneda del sitio, no un literal — pero PayPal NO admite PEN
            // (verificado contra su lista oficial de monedas). Si algún día el
            // sitio vuelve a soles, esto devuelve un error controlado en vez
            // de crear una orden en dólares por el importe en soles, que sería
            // cobrarle al cliente ~3.7× de lo que vio en pantalla.
            $currency = \App\Support\Money::site();

            if (! in_array($currency, \App\Services\PayPalService::SUPPORTED_CURRENCIES, true)) {
                Log::warning('checkout.paypal_create_order.unsupported_currency', ['currency' => $currency]);

                return response()->json(['error' => 'PayPal no está disponible para esta moneda. Usa tarjeta.'], 422);
            }

            // Si el carrito trae monedas mezcladas, el total no significa nada.
            if (! $this->cart->isSiteCurrencyOnly()) {
                Log::warning('checkout.paypal_create_order.currency_mismatch', [
                    'site_currency' => $currency,
                    'currencies' => $this->cart->currencies()->all(),
                ]);

                return response()->json(['error' => 'No pudimos iniciar el pago. Contáctanos por WhatsApp.'], 422);
            }

            // Amount always calculated server-side — never trust the client
            $total = $this->cart->total();

            $order = $this->paypal->createOrder($total, $currency, [
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
     */
    public function paypalCaptureOrder(Request $request, string $locale): JsonResponse
    {
        $validated = $request->validate([
            'orderID' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?\d{7,15}$/'],
            'travel_date' => ['required', 'date', 'after:today'],
            'pickup_point' => ['nullable', 'string', 'max:100'],
            'pickup_detail' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'El carrito está vacío.'], 422);
            }

            // Defense-in-depth: re-verify blocked dates before capturing payment
            $travelDate = $validated['travel_date'];
            foreach ($items as $item) {
                if (BlockedDate::isBlocked($travelDate, $item['tour_id'] ?? null)) {
                    Log::info('checkout.paypal_capture: blocked date rejected', [
                        'travel_date' => $travelDate,
                        'tour_id' => $item['tour_id'] ?? null,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => __('booking.date_blocked'),
                    ], 422);
                }
            }

            $captureResponse = $this->paypal->captureOrder($validated['orderID']);
            $captureId = $this->paypal->captureId($captureResponse);

            $customer = [
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'travel_date' => $validated['travel_date'],
                'pickup_point' => $validated['pickup_point'] ?? null,
                'pickup_detail' => $validated['pickup_detail'] ?? null,
            ];

            $bookings = $this->finalizeBookings($customer, 'paypal', $captureId, true);

            $request->session()->put('last_bookings', $bookings->toArray());

            return response()->json([
                'success' => true,
                'redirect' => route('checkout.thanks', ['locale' => $locale]),
            ]);

        } catch (\Throwable $e) {
            Log::error('checkout.paypal_capture_order.error', [
                'message' => $e->getMessage(),
                'order_id' => $validated['orderID'] ?? null,
                'email' => $validated['customer_email'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El pago no pudo completarse. Por favor inténtalo de nuevo o contáctanos.',
            ], 500);
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
     * Creates one Booking per cart item, sends confirmation emails,
     * clears the cart and returns the collection of created bookings.
     *
     * @param  array  $customer  Keys: customer_name, customer_email,
     *                           customer_phone, travel_date,
     *                           pickup_point, pickup_detail
     * @param  string  $method  'paypal' | 'pay_later' | 'culqi'
     * @param  string|null  $paymentReference  PayPal capture ID / Culqi charge id (null while pending)
     * @param  bool  $paid  true = mark as paid & confirmed
     * @param  bool  $notify  false skips the confirmation emails, abandoned-cart
     *                        closing and cart clearing — used by chargeWithCulqi()
     *                        to create the pending hold *before* attempting the
     *                        charge, without prematurely telling the customer
     *                        it succeeded or emptying their cart.
     * @return Collection<Booking>
     */
    private function finalizeBookings(
        array $customer,
        string $method,
        ?string $paymentReference,
        bool $paid,
        bool $notify = true,
    ): Collection {
        $locale = app()->getLocale();
        $items = $this->cart->items();
        $hasPickupColumns = Schema::hasColumn('bookings', 'pickup_point');

        // Resolve customer_id once before the map
        $customerId = $this->resolveCustomerId($customer, $locale);

        // El negocio es 100% PEN. Si el carrito trajera monedas mixtas (fuera
        // de alcance normal: los 26 tours reales son todos PEN), se registra
        // un warning y se fuerza PEN de todas formas — nunca queda en USD.
        $cartCurrencies = $items
            ->map(fn (array $item) => $this->resolveItemCurrency($item))
            ->unique();

        if ($cartCurrencies->count() > 1) {
            Log::warning('Carrito con monedas mixtas detectado', [
                'currencies' => $cartCurrencies->values()->all(),
                'tour_ids' => $items->pluck('tour_id')->all(),
                'email' => $customer['customer_email'],
            ]);
        }

        $bookings = $items->map(function (array $item) use (
            $customer, $method, $paymentReference, $paid, $locale, $hasPickupColumns, $customerId
        ): Booking {
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
                'currency' => $this->resolveItemCurrency($item),
                'status' => $paid ? 'confirmed' : 'pending',
                'payment_status' => $paid ? 'paid' : 'pending',
                'payment_method' => $method,
                'payment_reference' => $paymentReference,
                'locale' => $locale,
            ];

            if ($hasPickupColumns) {
                $attrs['pickup_point'] = $customer['pickup_point'] ?? null;
                $attrs['pickup_detail'] = $customer['pickup_detail'] ?? null;
            }

            return Booking::create($attrs);
        });

        Log::info('checkout.finalize_bookings', [
            'method' => $method,
            'paid' => $paid,
            'reference' => $paymentReference,
            'email' => $customer['customer_email'],
            'customer_id' => $customerId,
            'bookings' => $bookings->pluck('reference')->all(),
        ]);

        if ($notify) {
            // Send customer confirmation + internal admin notification (non-blocking).
            // Shared with the Filament admin "create booking" flow via BookingNotifier.
            $this->notifier->send($bookings, $paid, $customer['customer_email']);

            // Cierra el carrito abandonado asociado (por sesión y/o email)
            $this->abandoned->markConverted(session()->getId(), $customer['customer_email']);

            $this->cart->clear();
        }

        return $bookings;
    }

    /**
     * Resolves the currency to persist on a booking for a given cart item.
     *
     * Prefers the currency snapshotted on the cart item itself; falls back to
     * the tour's current currency; and finally to the site currency
     * (Money::site()) — nunca a un literal, para que la moneda del booking no
     * pueda divergir de la que se cobró.
     */
    private function resolveItemCurrency(array $item): string
    {
        if (! empty($item['currency'])) {
            return $item['currency'];
        }

        $tour = Tour::find($item['tour_id'] ?? null);

        return $tour?->currency ?: \App\Support\Money::site();
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
