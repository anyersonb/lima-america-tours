<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tour;
use App\Services\CartService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PayPal REACTIVADO (2026-07-29). Sustituye a PayPalPausedTest, que fijaba el
 * estado contrario: las dos rutas abortaban 404.
 *
 * El único bloqueo era la moneda: PayPal no admite PEN y el sitio cobraba en
 * soles, así que un endpoint vivo habría creado órdenes en dólares por el
 * importe en soles (~3.7× de sobrecobro). El cliente definió USD y con eso las
 * rutas vuelven a apuntar al controller.
 *
 * Lo que se fija ahora:
 *  1. Las rutas llegan al controller (ya NO son 404 ni 419 por CSRF).
 *  2. La orden se crea con la moneda del sitio, no con 'USD' hardcodeado.
 *  3. Si la moneda del sitio no es una que PayPal admita (alguien vuelve a
 *     poner PEN en el panel), el endpoint se niega y NO manda nada a PayPal:
 *     la regla que motivó la pausa sigue viva, ahora como código en vez de
 *     como ruta muerta.
 *  4. GET no está permitido: crear/capturar un pago es POST.
 */
class PayPalReactivatedTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function addTourToCart(string $currency = 'USD', float $price = 300.0): void
    {
        $tour = Tour::factory()->create([
            'price' => $price,
            'currency' => $currency,
            'is_published' => true,
        ]);

        app(CartService::class)->add($tour, 2, 0, now()->addDays(10)->format('Y-m-d'));
    }

    public function test_create_order_route_reaches_the_controller_and_is_no_longer_404(): void
    {
        Http::fake(); // carrito vacío: no debería salir nada hacia PayPal

        $response = $this->postJson(route('checkout.paypal.create', ['locale' => self::LOCALE]));

        // 422 = el controller respondió "carrito vacío". Lo que importa es que
        // no sea 404 (ruta muerta) ni 419 (CSRF interceptando antes).
        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_get_is_not_allowed_on_the_payment_routes(): void
    {
        $this->get(route('checkout.paypal.create', ['locale' => self::LOCALE]))->assertStatus(405);
        $this->get(route('checkout.paypal.capture', ['locale' => self::LOCALE]))->assertStatus(405);
    }

    public function test_order_is_created_in_the_site_currency(): void
    {
        Setting::set('site_currency', 'USD');
        Setting::set('paypal_client_id', 'sandbox-client-id');
        Setting::set('paypal_secret', 'sandbox-secret');
        Setting::set('paypal_mode', 'sandbox');

        $this->addTourToCart('USD', 300.0);

        Http::fake([
            '*oauth2/token*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
            '*checkout/orders*' => Http::response(['id' => 'ORDER-TEST-1', 'status' => 'CREATED']),
        ]);

        $response = $this->postJson(route('checkout.paypal.create', ['locale' => self::LOCALE]));

        $response->assertOk()->assertJson(['id' => 'ORDER-TEST-1']);

        // El importe viaja etiquetado con la moneda del sitio, no con un literal.
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'checkout/orders')) {
                return false;
            }

            $code = $request->data()['purchase_units'][0]['amount']['currency_code'] ?? null;

            return $code === Money::site() && $code === 'USD';
        });
    }

    public function test_paypal_refuses_a_currency_it_cannot_charge(): void
    {
        // Si alguien vuelve a poner el sitio en soles: PayPal no cobra PEN.
        Setting::set('site_currency', 'PEN');
        $this->addTourToCart('PEN', 300.0);

        Http::fake(); // cualquier llamada aquí sería el sobrecobro que motivó la pausa

        $response = $this->postJson(route('checkout.paypal.create', ['locale' => self::LOCALE]));

        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_mixed_currency_cart_never_reaches_paypal(): void
    {
        Setting::set('site_currency', 'USD');

        $this->addTourToCart('USD', 300.0);
        $this->addTourToCart('PEN', 300.0); // segundo tour, otra moneda

        Http::fake();

        $response = $this->postJson(route('checkout.paypal.create', ['locale' => self::LOCALE]));

        $response->assertStatus(422);
        Http::assertNothingSent();
    }
}
