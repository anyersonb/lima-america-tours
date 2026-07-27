<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO S-06: checkout.blade.php, checkout/thanks.blade.php y
 * checkout/payment.blade.php imprimían su propio
 * <meta name="robots" content="noindex,nofollow"> (vía @push('head')) ADEMÁS
 * del que ya imprime resources/views/layouts/app.blade.php (condicionado por
 * env('NOINDEX')) — 2 tags <meta name="robots"> en el mismo <head>.
 *
 * Fix: las 3 vistas ahora declaran @section('robots', 'noindex,nofollow'),
 * que el layout prioriza sobre su propia lógica de NOINDEX/env — una sola
 * etiqueta, siempre noindex,nofollow en estas 3 rutas.
 */
class CheckoutRobotsMetaTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function assertSingleNoindexRobotsMeta(string $html): void
    {
        $count = substr_count($html, '<meta name="robots"');

        $this->assertSame(1, $count, "expected exactly 1 <meta name=\"robots\"> tag, found {$count}");
        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $html);
    }

    public function test_cart_index_has_a_single_noindex_robots_meta(): void
    {
        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);
        app(CartService::class)->add($tour, 1, 0, now()->addDays(5)->format('Y-m-d'));

        $response = $this->get(route('cart.index', ['locale' => self::LOCALE]));

        $response->assertOk();
        $this->assertSingleNoindexRobotsMeta($response->getContent());
    }

    public function test_checkout_payment_form_has_a_single_noindex_robots_meta(): void
    {
        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);
        app(CartService::class)->add($tour, 1, 0, now()->addDays(5)->format('Y-m-d'));

        $response = $this->get(route('checkout.pay', ['locale' => self::LOCALE]));

        $response->assertOk();
        $this->assertSingleNoindexRobotsMeta($response->getContent());
    }

    public function test_checkout_thanks_has_a_single_noindex_robots_meta(): void
    {
        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'tour_title_snapshot' => $tour->title_es,
            'customer_name' => 'Ana López',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '987000001',
            'travel_date' => now()->addDays(10)->format('Y-m-d'),
            'adults' => 2,
            'children' => 0,
            'unit_price' => 100.00,
            'total_price' => 200.00,
            'currency' => 'PEN',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'culqi',
            'payment_reference' => 'chr_test_robots',
            'locale' => 'es',
        ]);

        $response = $this->withSession(['last_bookings' => [$booking->toArray()]])
            ->get(route('checkout.thanks', ['locale' => self::LOCALE]));

        $response->assertOk();
        $this->assertSingleNoindexRobotsMeta($response->getContent());
    }
}
