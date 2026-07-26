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
 * Integration coverage for the "guarda anti-cobro-real"
 * (App\Services\PaymentGuard): if LIVE credentials ever leak into a
 * non-production environment, the checkout must fail in a controlled way —
 * no real HTTP call to the gateway, no booking marked as paid — instead of
 * a 500 or, worse, an actual charge.
 */
class PaymentGuardCheckoutTest extends TestCase
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

    public function test_culqi_live_key_in_a_non_production_environment_blocks_the_charge_without_calling_culqi(): void
    {
        Mail::fake();
        Http::fake(); // any call reaching Culqi here would be a bug

        config(['services.culqi.secret_key' => 'sk_live_real_money']);

        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->post(route('checkout.process', ['locale' => self::LOCALE]), [
            'customer_name' => 'Juan Pérez García',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(15)->format('Y-m-d'),
            'culqi_token' => 'tkn_test_whatever',
        ]);

        // Controlled failure: redirect back with a friendly error, not a 500.
        $response->assertRedirect();
        $response->assertSessionHas('error');

        Http::assertNothingSent();

        // The hold was created but must be marked failed — never paid.
        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'payment_status' => 'failed',
        ]);
        $this->assertDatabaseMissing('bookings', [
            'customer_email' => 'juan@example.com',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * PayPal is now paused server-side (routes/web.php disables
     * checkout.paypal.capture / .create — see the "PayPal EN PAUSA" comment
     * there, pending the currency decision in docs/pagos/PLAN-PASARELAS.md
     * §13.2). That route-level block is a strictly stronger guarantee than
     * the old scenario this test covered (a LIVE-mode leak being caught
     * inside PayPalService::captureOrder via PaymentGuard): the request
     * never reaches the controller/gateway at all, so PaymentGuard is not
     * even exercised. This test now asserts that stronger invariant instead.
     * PaymentGuard's own coverage for PayPal (isLive/assertChargeAllowed)
     * remains intact and untouched in tests/Unit/PaymentGuardTest.php.
     */
    public function test_paypal_live_mode_in_a_non_production_environment_blocks_the_capture_without_calling_paypal(): void
    {
        Mail::fake();
        Http::fake(); // any call reaching PayPal here would be a bug

        config(['services.paypal.mode' => 'live']);

        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->postJson(
            route('checkout.paypal.capture', ['locale' => self::LOCALE]),
            [
                'orderID' => 'ORDER-LIVE-LEAK',
                'customer_name' => 'Juan Pérez García',
                'customer_email' => 'juan@example.com',
                'customer_phone' => '987654321',
                'travel_date' => now()->addDays(15)->format('Y-m-d'),
            ]
        );

        // The route itself is disabled while PayPal is paused — 404 before
        // any controller/gateway/guard code runs.
        $response->assertStatus(404);

        Http::assertNothingSent();

        $this->assertDatabaseMissing('bookings', ['customer_email' => 'juan@example.com']);
    }
}
