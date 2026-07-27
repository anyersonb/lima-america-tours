<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gate SEO (QA de reserva): checkout.blade.php imprimía DOS <h1> en el mismo
 * DOM — el hero "Carrito" (#cart-hero-carrito, paso 1, visible por defecto) y
 * el hero "Detalles de la Reserva" (#cart-hero-detalles, paso 2, oculto vía
 * `style="display:none"` pero igual presente en el HTML servido). Ambos
 * existen simultáneamente en el markup aunque JS solo muestre uno a la vez,
 * así que un crawler ve 2 <h1> en /es/carrito.
 *
 * Fix: se conserva como <h1> el título de la pantalla por defecto (paso 1,
 * "Carrito" — coincide con <title> vía __('ui.cart_title')) y se degrada el
 * segundo (paso 2, "Detalles de la Reserva") a <h2>, sin tocar texto ni clase.
 */
class CheckoutSingleH1Test extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    public function test_cart_index_renders_exactly_one_h1(): void
    {
        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);
        app(CartService::class)->add($tour, 1, 0, now()->addDays(5)->format('Y-m-d'));

        $response = $this->get(route('cart.index', ['locale' => self::LOCALE]));

        $response->assertOk();

        $count = substr_count($response->getContent(), '<h1');

        $this->assertSame(1, $count, "expected exactly 1 <h1> tag, found {$count}");
    }
}
