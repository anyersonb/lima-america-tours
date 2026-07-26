<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayPalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    private function tour(array $overrides = []): Tour
    {
        return Tour::factory()->create(array_merge([
            'price' => 150.00,
            'is_published' => true,
        ], $overrides));
    }

    private function addTourToCart(Tour $tour): void
    {
        app(CartService::class)->add($tour, 2, 1, now()->addDays(10)->format('Y-m-d'));
    }

    private function fakeAccessToken(): array
    {
        return [
            '*/v1/oauth2/token' => Http::response(['access_token' => 'A21AAtest-token'], 200),
        ];
    }

    private function validCapturePayload(array $overrides = []): array
    {
        return array_merge([
            'orderID' => 'ORDER-TEST-1',
            'customer_name' => 'Juan Pérez García',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(15)->format('Y-m-d'),
        ], $overrides);
    }

    private function fakeCaptureResponse(string $captureId, string $status = 'COMPLETED'): array
    {
        return [
            'status' => $status,
            'purchase_units' => [
                ['payments' => ['captures' => [['id' => $captureId]]]],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Tests
    // ─────────────────────────────────────────────────────────────

    public function test_create_order_returns_paypal_order_id(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        Http::fake(array_merge($this->fakeAccessToken(), [
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDER-TEST-1'], 201),
        ]));

        $response = $this->postJson(
            route('checkout.paypal.create', ['locale' => self::LOCALE])
        );

        $response->assertOk();
        $response->assertJson(['id' => 'ORDER-TEST-1']);
    }

    public function test_capture_order_creates_paid_booking(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        Http::fake(array_merge($this->fakeAccessToken(), [
            '*/v2/checkout/orders/*/capture' => Http::response($this->fakeCaptureResponse('CAPTURE-1'), 201),
        ]));

        $response = $this->postJson(
            route('checkout.paypal.capture', ['locale' => self::LOCALE]),
            $this->validCapturePayload()
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'payment_method' => 'paypal',
            'payment_reference' => 'CAPTURE-1',
        ]);
    }

    /**
     * docs/pagos/PLAN-PASARELAS.md §5.3/§6: a double click (or a client retry
     * after the first response was lost) must not create a second Booking.
     * PayPal itself rejects a second /capture call on an already-captured
     * order with ORDER_ALREADY_CAPTURED — the controller must treat that as
     * "already done" (fetch the existing capture via getOrder()) rather than
     * as a failure, and must not duplicate the Booking once it finds the
     * capture id already stored.
     */
    public function test_double_capture_does_not_duplicate_booking(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        Http::fake(array_merge($this->fakeAccessToken(), [
            '*/v2/checkout/orders/*/capture' => Http::response($this->fakeCaptureResponse('CAPTURE-DUP'), 201),
        ]));

        $first = $this->postJson(
            route('checkout.paypal.capture', ['locale' => self::LOCALE]),
            $this->validCapturePayload(['orderID' => 'ORDER-DUP'])
        );
        $first->assertOk();
        $first->assertJson(['success' => true]);

        // Re-add to cart to simulate the user's browser still holding the
        // same (unsubmitted-looking) page and re-triggering the capture call.
        $this->addTourToCart($tour);

        Http::fake(array_merge($this->fakeAccessToken(), [
            // Second attempt: PayPal reports the order was already captured.
            '*/v2/checkout/orders/*/capture' => Http::response([
                'name' => 'UNPROCESSABLE_ENTITY',
                'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']],
            ], 422),
            '*/v2/checkout/orders/ORDER-DUP' => Http::response($this->fakeCaptureResponse('CAPTURE-DUP'), 200),
        ]));

        $second = $this->postJson(
            route('checkout.paypal.capture', ['locale' => self::LOCALE]),
            $this->validCapturePayload(['orderID' => 'ORDER-DUP'])
        );

        $second->assertOk();
        $second->assertJson(['success' => true]);

        $this->assertSame(
            1,
            Booking::where('payment_method', 'paypal')->where('payment_reference', 'CAPTURE-DUP')->count()
        );
    }

    public function test_capture_order_rejects_blocked_travel_date(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        // Recurring weekday block (not an exact date match) — an exact
        // `date` block compares as a raw string and SQLite stores dates
        // with a time component (e.g. "2026-08-10 00:00:00"), so it never
        // matches a plain "Y-m-d" query in this test driver. `weekday` is a
        // plain integer compare and is unaffected, so it exercises the same
        // BlockedDate::isBlocked() guard reliably here.
        \App\Models\BlockedDate::create([
            'weekday' => now()->addDays(15)->dayOfWeek,
            'tour_id' => null,
        ]);

        Http::fake($this->fakeAccessToken());

        $response = $this->postJson(
            route('checkout.paypal.capture', ['locale' => self::LOCALE]),
            $this->validCapturePayload()
        );

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseMissing('bookings', ['customer_email' => 'juan@example.com']);
    }
}
