<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * CRO #1: CartService::subtotal() sumaba el `subtotal` de cada item sin mirar
 * su moneda, y el cobro mandaba la moneda hardcodeada a Culqi sin verificar
 * que el total fuera realmente de esa moneda. Con tours en dos monedas en la
 * misma BD, el carrito sumaba soles y dólares como si fueran el mismo número
 * y la pasarela cobraba ese total con una etiqueta que no le correspondía
 * (~3.7× de sobrecobro).
 *
 * Fix: CartService::add() snapshotea `currency` por item, y
 * CheckoutController::processPayment() aborta ANTES de calcular/cobrar nada si
 * el carrito trae alguna moneda distinta de la del sitio — con un error en
 * español, nunca un 500, y sin llegar a tocar la pasarela.
 *
 * La moneda del sitio pasó de PEN a USD el 2026-07-29 (decisión del cliente),
 * así que aquí el "tour intruso" es ahora el que está en soles. La guarda se
 * compara contra Money::site(), no contra un literal: es lo que hace que este
 * test siga significando lo mismo si la moneda vuelve a cambiar.
 */
class CheckoutCurrencyGuardTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function customerPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Juan Pérez García',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(15)->format('Y-m-d'),
        ], $overrides);
    }

    public function test_pay_later_is_rejected_when_cart_has_a_foreign_currency_tour(): void
    {
        Mail::fake();
        Http::fake();

        $foreignTour = Tour::factory()->create([
            'price' => 500.00,
            'currency' => 'PEN', // moneda distinta a la del sitio (USD)
            'is_published' => false,
        ]);

        $this->cartService()->add($foreignTour, 2, 0, now()->addDays(10)->format('Y-m-d'));

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->customerPayload(['payment_timing' => 'later'])
        );

        $response->assertRedirectToRoute('cart.index', ['locale' => self::LOCALE]);
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        Http::assertNothingSent();
    }

    public function test_pay_now_is_rejected_and_culqi_is_never_called_when_cart_has_a_foreign_currency_tour(): void
    {
        Mail::fake();
        Http::fake(); // any request here would mean a mixed-currency charge slipped through

        $foreignTour = Tour::factory()->create([
            'price' => 500.00,
            'currency' => 'PEN', // moneda distinta a la del sitio (USD)
            'is_published' => false,
        ]);

        $this->cartService()->add($foreignTour, 1, 0, now()->addDays(10)->format('Y-m-d'));

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->customerPayload(['payment_timing' => 'now', 'culqi_token' => 'tkn_test_abc123'])
        );

        $response->assertRedirectToRoute('cart.index', ['locale' => self::LOCALE]);
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        Http::assertNothingSent();
    }

    public function test_pay_later_still_succeeds_when_the_whole_cart_is_in_the_site_currency(): void
    {
        Mail::fake();

        $tour = Tour::factory()->create([
            'price' => 150.00,
            'currency' => \App\Support\Money::site(),
            'is_published' => true,
        ]);

        $this->cartService()->add($tour, 2, 0, now()->addDays(10)->format('Y-m-d'));

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->customerPayload(['payment_timing' => 'later'])
        );

        $response->assertRedirectToRoute('checkout.thanks', ['locale' => self::LOCALE]);
        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'currency' => \App\Support\Money::site(),
        ]);
    }
}
