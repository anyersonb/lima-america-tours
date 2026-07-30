<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El carrito ofrece "Pagar ahora" solo cuando hay una pasarela configurada, y
 * el aviso que acompaña al botón dice la verdad.
 *
 * El carrito prometía por texto fijo "Sin pago en línea por ahora". Con el
 * pago activo ese aviso contradice al botón que está justo debajo, y sin él un
 * botón de pago lleva al cliente a llenar todo para fallar en el último clic.
 * Las dos mitades salen ahora del mismo dato ($onlinePayment, resuelto en
 * CartController).
 */
class CartPayOnlineCtaTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function fillCart(): void
    {
        $tour = Tour::factory()->create(['price' => 300.00, 'is_published' => true]);

        app(CartService::class)->add($tour, 2, 0, now()->addDays(10)->format('Y-m-d'));
    }

    public function test_without_a_gateway_there_is_no_pay_button_and_the_notice_says_so(): void
    {
        config(['services.culqi.public_key' => 'pk_test_REPLACE_ME']);

        $this->fillCart();

        $response = $this->get(route('cart.index', ['locale' => self::LOCALE]))->assertOk();

        $response->assertDontSee('id="btn-pay-online"', false);
        $response->assertSee('Sin pago en línea por ahora', false);
    }

    public function test_with_a_gateway_the_pay_button_appears_and_the_notice_changes(): void
    {
        $this->fillCart();
        Setting::set('culqi_public_key', 'pk_test_llave_de_prueba');
        Setting::set('culqi_secret_key', 'sk_test_llave_de_prueba');

        $response = $this->get(route('cart.index', ['locale' => self::LOCALE]))->assertOk();

        $response->assertSee('id="btn-pay-online"', false);
        $response->assertSee(route('checkout.pay', ['locale' => self::LOCALE]), false);
        $response->assertSee('Pago seguro en línea', false);
        $response->assertDontSee('Sin pago en línea por ahora', false);
    }
}
