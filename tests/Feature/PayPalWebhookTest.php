<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paypal.webhook_id' => 'WH-TEST-ID']);
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    private function fakeAccessToken(): array
    {
        return [
            '*/v1/oauth2/token' => Http::response(['access_token' => 'A21AAtest-token'], 200),
        ];
    }

    private function fakeVerifySignature(string $status = 'SUCCESS'): array
    {
        return [
            '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => $status], 200),
        ];
    }

    private function makeBooking(array $overrides = []): Booking
    {
        $tour = Tour::factory()->create(['price' => 100.00]);

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_title_snapshot' => $tour->title_es,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(5)->format('Y-m-d'),
            'adults' => 1,
            'children' => 0,
            'unit_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'paypal',
            'payment_reference' => null,
            'locale' => 'es',
        ], $overrides));
    }

    private function postWebhook(array $event): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            'POST',
            route('webhooks.paypal'),
            [],
            [],
            [],
            [
                'HTTP_Paypal-Transmission-Id' => 'txn-1',
                'HTTP_Paypal-Transmission-Time' => now()->toIso8601String(),
                'HTTP_Paypal-Cert-Url' => 'https://api.sandbox.paypal.com/cert',
                'HTTP_Paypal-Auth-Algo' => 'SHA256withRSA',
                'HTTP_Paypal-Transmission-Sig' => 'fake-signature',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($event)
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Tests
    // ─────────────────────────────────────────────────────────────

    public function test_webhook_rejects_invalid_signature(): void
    {
        Http::fake(array_merge($this->fakeAccessToken(), $this->fakeVerifySignature('FAILURE')));

        $response = $this->postWebhook([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAPTURE-BAD'],
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_capture_completed_updates_booking(): void
    {
        $booking = $this->makeBooking(['payment_reference' => 'CAPTURE-OK']);

        Http::fake(array_merge($this->fakeAccessToken(), $this->fakeVerifySignature('SUCCESS')));

        $response = $this->postWebhook([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAPTURE-OK'],
        ]);

        $response->assertOk();
        $response->assertJson(['received' => true]);

        $booking->refresh();
        $this->assertEquals('paid', $booking->payment_status);
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_webhook_capture_denied_marks_booking_failed(): void
    {
        $booking = $this->makeBooking(['payment_reference' => 'CAPTURE-DENIED']);

        Http::fake(array_merge($this->fakeAccessToken(), $this->fakeVerifySignature('SUCCESS')));

        $response = $this->postWebhook([
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => ['id' => 'CAPTURE-DENIED'],
        ]);

        $response->assertOk();

        $booking->refresh();
        $this->assertEquals('failed', $booking->payment_status);
    }

    public function test_webhook_capture_refunded_updates_booking_and_refund_fields(): void
    {
        $booking = $this->makeBooking([
            'payment_reference' => 'CAPTURE-REFUND',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        Http::fake(array_merge($this->fakeAccessToken(), $this->fakeVerifySignature('SUCCESS')));

        $response = $this->postWebhook([
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND-1',
                'amount' => ['value' => '150.00', 'currency_code' => 'USD'],
                'links' => [
                    ['rel' => 'up', 'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/CAPTURE-REFUND'],
                ],
            ],
        ]);

        $response->assertOk();

        $booking->refresh();
        $this->assertEquals('refunded', $booking->payment_status);
        $this->assertEquals('refunded', $booking->status);
        $this->assertNotNull($booking->refunded_at);
        $this->assertEquals('150.00', (string) $booking->refund_amount);
    }

    public function test_webhook_is_idempotent(): void
    {
        $booking = $this->makeBooking([
            'payment_reference' => 'CAPTURE-IDEMPOTENT',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        Http::fake(array_merge($this->fakeAccessToken(), $this->fakeVerifySignature('SUCCESS')));

        $event = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAPTURE-IDEMPOTENT'],
        ];

        $this->postWebhook($event)->assertOk();
        $this->postWebhook($event)->assertOk();

        $booking->refresh();
        $this->assertEquals('paid', $booking->payment_status);
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_webhook_with_no_matching_booking_is_logged_and_ignored(): void
    {
        Http::fake(array_merge($this->fakeAccessToken(), $this->fakeVerifySignature('SUCCESS')));

        $response = $this->postWebhook([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAPTURE-UNKNOWN'],
        ]);

        // Still 200s — PayPal must not retry forever on an event we can't
        // reconcile; the gap is logged for manual follow-up (see
        // docs/pagos/IMPLEMENTACION-SANDBOX.md).
        $response->assertOk();
        $this->assertDatabaseCount('bookings', 0);
    }
}
