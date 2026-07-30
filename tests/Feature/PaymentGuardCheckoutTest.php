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
     * PayPal volvió a estar activo (2026-07-29), así que la captura SÍ llega al
     * controller y esta prueba vuelve a su escenario original: credenciales
     * LIVE en un entorno no productivo tienen que morir dentro de
     * PayPalService::captureOrder por PaymentGuard, sin que salga una sola
     * request hacia PayPal y sin que ninguna reserva quede como pagada.
     *
     * Mientras PayPal estuvo en pausa esto se comprobaba por la vía de la ruta
     * deshabilitada (404). Ese 404 ya no existe: si esta prueba se hubiera
     * dejado como estaba, seguiría "verde" describiendo un mundo que no es el
     * actual, o peor, en rojo tapando que el guard sí funciona.
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

        // 500 con mensaje genérico: el controller atrapa la
        // RealChargeBlockedException del guard como cualquier otro fallo de
        // pago. Lo que importa no es el código, sino las dos líneas de abajo.
        $response->assertStatus(500);
        $response->assertJson(['success' => false]);

        // Ni una request a PayPal: el guard corta ANTES de la red.
        Http::assertNothingSent();

        $this->assertDatabaseMissing('bookings', ['customer_email' => 'juan@example.com']);
    }
}
