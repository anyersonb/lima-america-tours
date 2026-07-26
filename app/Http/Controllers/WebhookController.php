<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingStateMachine;
use App\Services\PaymentService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $payment,
        private readonly PayPalService $paypal,
        private readonly BookingStateMachine $stateMachine,
    ) {}

    /**
     * Handle incoming Culqi webhook events.
     *
     * Events handled:
     *  - charge.succeeded
     *  - charge.failed
     */
    public function culqi(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Culqi-Signature', '');

        // Validate HMAC signature
        if (! $this->payment->verifyWebhookSignature($payload, $signature)) {
            Log::warning('webhook.culqi.invalid_signature', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);

            abort(400, 'Invalid webhook signature.');
        }

        $event = json_decode($payload, true);
        $eventType = $event['type'] ?? null;
        $chargeId = $event['data']['id'] ?? null;

        Log::info('webhook.culqi.received', [
            'type' => $eventType,
            'charge_id' => $chargeId,
        ]);

        match ($eventType) {
            'charge.succeeded' => $this->handleCulqiChargeSucceeded($chargeId),
            'charge.failed' => $this->handleCulqiChargeFailed($chargeId),
            default => Log::info('webhook.culqi.unhandled_event', ['type' => $eventType]),
        };

        return response()->json(['received' => true]);
    }

    /**
     * Handle incoming PayPal webhook events (docs/pagos/PLAN-PASARELAS.md §5.2).
     *
     * Events handled:
     *  - PAYMENT.CAPTURE.COMPLETED
     *  - PAYMENT.CAPTURE.DENIED / PAYMENT.CAPTURE.DECLINED
     *  - PAYMENT.CAPTURE.REFUNDED
     *
     * Reconciles Bookings already created by CheckoutController::paypalCaptureOrder()
     * (matched by payment_reference = capture id). It never creates Bookings —
     * there is no customer data available at webhook time — so a race where the
     * capture succeeds at PayPal but our own captureOrder() call never returns
     * still requires the customer to retry (documented limitation, see
     * docs/pagos/IMPLEMENTACION-SANDBOX.md).
     */
    public function paypal(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $event = json_decode($payload, true);

        if (! is_array($event)) {
            Log::warning('webhook.paypal.invalid_json', ['ip' => $request->ip()]);
            abort(400, 'Invalid webhook payload.');
        }

        $headers = [
            'transmission_id' => $request->header('Paypal-Transmission-Id'),
            'transmission_time' => $request->header('Paypal-Transmission-Time'),
            'cert_url' => $request->header('Paypal-Cert-Url'),
            'auth_algo' => $request->header('Paypal-Auth-Algo'),
            'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
        ];

        if (! $this->paypal->verifyWebhookSignature($headers, $event)) {
            Log::warning('webhook.paypal.invalid_signature', ['ip' => $request->ip()]);
            abort(400, 'Invalid webhook signature.');
        }

        $eventType = $event['event_type'] ?? null;
        $resource = $event['resource'] ?? [];

        Log::info('webhook.paypal.received', [
            'type' => $eventType,
            'resource_id' => $resource['id'] ?? null,
        ]);

        match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->handlePayPalCaptureCompleted($resource['id'] ?? null),
            'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' => $this->handlePayPalCaptureDenied($resource['id'] ?? null),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handlePayPalCaptureRefunded($resource),
            default => Log::info('webhook.paypal.unhandled_event', ['type' => $eventType]),
        };

        return response()->json(['received' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Culqi handlers
    // ─────────────────────────────────────────────────────────────

    private function handleCulqiChargeSucceeded(?string $chargeId): void
    {
        $bookings = $this->findBookings('culqi', $chargeId, 'charge_succeeded');

        foreach ($bookings as $booking) {
            $this->stateMachine->transition($booking, BookingStateMachine::PAID);
        }
    }

    private function handleCulqiChargeFailed(?string $chargeId): void
    {
        $bookings = $this->findBookings('culqi', $chargeId, 'charge_failed');

        foreach ($bookings as $booking) {
            $this->stateMachine->transition($booking, BookingStateMachine::FAILED);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PayPal handlers
    // ─────────────────────────────────────────────────────────────

    private function handlePayPalCaptureCompleted(?string $captureId): void
    {
        $bookings = $this->findBookings('paypal', $captureId, 'capture_completed');

        foreach ($bookings as $booking) {
            $this->stateMachine->transition($booking, BookingStateMachine::PAID);
        }
    }

    private function handlePayPalCaptureDenied(?string $captureId): void
    {
        $bookings = $this->findBookings('paypal', $captureId, 'capture_denied');

        foreach ($bookings as $booking) {
            $this->stateMachine->transition($booking, BookingStateMachine::FAILED);
        }
    }

    private function handlePayPalCaptureRefunded(array $resource): void
    {
        $captureId = $this->extractCaptureIdFromRefund($resource);

        if (! $captureId) {
            Log::warning('webhook.paypal.capture_refunded.no_capture_id', ['refund_id' => $resource['id'] ?? null]);

            return;
        }

        $bookings = $this->findBookings('paypal', $captureId, 'capture_refunded');
        $amount = $resource['amount']['value'] ?? null;

        foreach ($bookings as $booking) {
            $this->stateMachine->transition($booking, BookingStateMachine::REFUNDED, [
                'refunded_at' => now(),
                'refund_amount' => $amount,
            ]);
        }
    }

    /**
     * A refund webhook's resource is the refund itself (its own `id`), not
     * the capture — the capture id is only reachable via the `links` array
     * (rel="up" points back to /v2/payments/captures/{capture_id}).
     */
    private function extractCaptureIdFromRefund(array $resource): ?string
    {
        foreach ($resource['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'up' && ! empty($link['href'])) {
                $segments = explode('/', rtrim($link['href'], '/'));

                return end($segments) ?: null;
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  Shared lookup
    // ─────────────────────────────────────────────────────────────

    /**
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    private function findBookings(string $paymentMethod, ?string $reference, string $context): \Illuminate\Support\Collection
    {
        if (! $reference) {
            Log::warning("webhook.{$paymentMethod}.{$context}.missing_reference");

            return collect();
        }

        $bookings = Booking::where('payment_method', $paymentMethod)
            ->where('payment_reference', $reference)
            ->get();

        if ($bookings->isEmpty()) {
            Log::warning("webhook.{$paymentMethod}.{$context}.no_bookings", ['reference' => $reference]);
        }

        return $bookings;
    }
}
