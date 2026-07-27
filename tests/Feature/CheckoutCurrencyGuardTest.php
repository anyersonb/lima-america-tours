<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * CRO #1: CartService::subtotal() sumaba `subtotal` de cada item sin mirar
 * su moneda, y CheckoutController::chargeWithCulqi() mandaba
 * 'currency' => 'PEN' hardcodeado a Culqi sin verificar que el total
 * realmente fuera en soles. Los 26 tours públicos son PEN, pero ya existen
 * 7 tours currency=USD en la BD (borradores, docs/cro/VALIDACION-CRO.md
 * hallazgo #1/#4) — el día que uno se publicara o un tour cambiara de
 * moneda por error de captura, el carrito sumaría soles y dólares como si
 * fueran la misma moneda y Culqi cobraría ese total etiquetado como PEN.
 *
 * Fix: CartService::add() ahora snapshotea `currency` por item, y
 * CheckoutController::processPayment() aborta ANTES de calcular/cobrar
 * nada si el carrito trae alguna moneda distinta de PEN — con un error en
 * español, nunca un 500, y sin llegar a tocar Culqi.
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

    public function test_pay_later_is_rejected_when_cart_has_a_non_pen_tour(): void
    {
        Mail::fake();
        Http::fake();

        $usdTour = Tour::factory()->create([
            'price' => 500.00,
            'currency' => 'USD',
            'is_published' => false, // matches the real USD drafts in the DB
        ]);

        $this->cartService()->add($usdTour, 2, 0, now()->addDays(10)->format('Y-m-d'));

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->customerPayload(['payment_timing' => 'later'])
        );

        $response->assertRedirectToRoute('cart.index', ['locale' => self::LOCALE]);
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        Http::assertNothingSent();
    }

    public function test_pay_now_is_rejected_and_culqi_is_never_called_when_cart_has_a_non_pen_tour(): void
    {
        Mail::fake();
        Http::fake(); // any request here would mean a mixed-currency charge slipped through

        $usdTour = Tour::factory()->create([
            'price' => 500.00,
            'currency' => 'USD',
            'is_published' => false,
        ]);

        $this->cartService()->add($usdTour, 1, 0, now()->addDays(10)->format('Y-m-d'));

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->customerPayload(['payment_timing' => 'now', 'culqi_token' => 'tkn_test_abc123'])
        );

        $response->assertRedirectToRoute('cart.index', ['locale' => self::LOCALE]);
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        Http::assertNothingSent();
    }

    public function test_pay_later_still_succeeds_when_the_whole_cart_is_pen(): void
    {
        Mail::fake();

        $penTour = Tour::factory()->create([
            'price' => 150.00,
            'currency' => 'PEN',
            'is_published' => true,
        ]);

        $this->cartService()->add($penTour, 2, 0, now()->addDays(10)->format('Y-m-d'));

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->customerPayload(['payment_timing' => 'later'])
        );

        $response->assertRedirectToRoute('checkout.thanks', ['locale' => self::LOCALE]);
        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'currency' => 'PEN',
        ]);
    }
}
