<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * docs/pagos/PLAN-PASARELAS.md §6: "Doble cobro / carrera navegador-webhook".
 * Covers the Culqi side of the idempotency requirement — a double form
 * submit (double click on "Pagar ahora") must not create two charges/two
 * Booking sets for the same cart.
 */
class CulqiChargeIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function tour(): Tour
    {
        return Tour::factory()->create(['price' => 150.00, 'is_published' => true]);
    }

    private function addTourToCart(Tour $tour): void
    {
        app(CartService::class)->add($tour, 2, 1, now()->addDays(10)->format('Y-m-d'));
    }

    private function payload(): array
    {
        return [
            'customer_name' => 'Juan Pérez García',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(15)->format('Y-m-d'),
            'culqi_token' => 'tkn_test_double_click',
        ];
    }

    public function test_double_submit_of_the_same_cart_does_not_duplicate_the_paid_booking(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_test_double_click',
                'amount' => 45000,
                'currency_code' => 'USD',
                'object' => 'charge',
                'outcome' => ['type' => 'venta_exitosa'],
            ], 201),
        ]);

        $payload = $this->payload();

        $first = $this->post(route('checkout.process', ['locale' => self::LOCALE]), $payload);
        $first->assertRedirectToRoute('checkout.thanks', ['locale' => self::LOCALE]);

        // The first request cleared the cart on success. A double click that
        // reaches the server after that must NOT create a second booking —
        // there is nothing left in the cart to charge again.
        $second = $this->post(route('checkout.process', ['locale' => self::LOCALE]), $payload);
        $second->assertRedirectToRoute('cart.index', ['locale' => self::LOCALE]);

        $this->assertSame(
            1,
            Booking::where('payment_reference', 'chr_test_double_click')->count()
        );
    }

    public function test_failed_charge_keeps_the_hold_alive_for_a_retry_without_duplicating_it(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'object' => 'error',
                'user_message' => 'La tarjeta fue rechazada.',
            ], 422),
        ]);

        $response = $this->post(route('checkout.process', ['locale' => self::LOCALE]), $this->payload());

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Exactly one held booking per cart item, all marked failed —
        // the cart was NOT cleared, so the customer can retry.
        $this->assertSame(1, Booking::where('customer_email', 'juan@example.com')->count());
        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'payment_status' => 'failed',
            'status' => 'pending',
        ]);
    }
}
