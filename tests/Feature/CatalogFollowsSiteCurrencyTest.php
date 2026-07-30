<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tour;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El catálogo muestra la moneda EN LA QUE SE VA A COBRAR, no la etiqueta que
 * arrastre cada tour en su columna `currency`.
 *
 * Hallazgo del CRO (2026-07-29, severidad alta): el listado, la ficha y los
 * resultados de búsqueda formateaban con `$tour->currency`, mientras el
 * carrito y el checkout ya usaban `Money::site()`. Con "Moneda del sitio" en
 * soles y los tours etiquetados en dólares, el visitante veía "$" en el
 * catálogo y "S/" al pagar: la misma clase de bug de moneda mezclada que costó
 * el sobrecobro de ~3.7× y que el propio comentario de Money.php dice evitar.
 *
 * `tours.currency` NO desaparece: sigue siendo el snapshot por ítem que usa la
 * guarda del checkout (`CartService::isSiteCurrencyOnly()`) para negarse a
 * cobrar un tour etiquetado en otra moneda. Lo que cambia es que el PRECIO que
 * se muestra al público sale de una sola fuente.
 */
class CatalogFollowsSiteCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    public function test_catalog_and_detail_show_the_site_currency_even_if_the_tour_says_otherwise(): void
    {
        Setting::set('site_currency', 'USD');

        // Tour mal etiquetado a propósito (es el caso que produjo el hallazgo).
        $tour = Tour::factory()->create([
            'title_es' => 'Tour con moneda desalineada',
            'price' => 120.00,
            'price_before' => 200.00,
            'currency' => 'PEN',
            'is_published' => true,
        ]);

        $this->assertSame('USD', Money::site());
        $wrongPrefix = Money::prefix('PEN');

        foreach ([
            route('tours.index', ['locale' => self::LOCALE]),
            route('tours.show', ['locale' => self::LOCALE, 'slug' => $tour->slug]),
        ] as $url) {
            $response = $this->get($url)->assertOk();

            $response->assertSee(Money::format($tour->price, Money::site()), false);
            $response->assertDontSee($wrongPrefix, false);
        }
    }

    public function test_structured_data_declares_the_currency_that_is_charged(): void
    {
        Setting::set('site_currency', 'USD');

        $tour = Tour::factory()->create([
            'price' => 120.00,
            'currency' => 'PEN', // etiqueta vieja en la fila
            'is_published' => true,
        ]);

        // Google debe leer la moneda del cobro real, no la de la columna: un
        // priceCurrency que no coincide con lo que se cobra es motivo de
        // desaprobación en Merchant/rich results.
        //
        // Se normalizan los espacios antes de comparar: el JSON-LD se emite con
        // pretty print y fijar el espaciado exacto haría que este test se rompa
        // por un cambio de formato que no cambia nada para Google.
        $html = $this->get(route('tours.show', ['locale' => self::LOCALE, 'slug' => $tour->slug]))
            ->assertOk()
            ->getContent();

        $normalized = preg_replace('/\s+/', '', $html);

        $this->assertStringContainsString('"priceCurrency":"USD"', $normalized);
        $this->assertStringNotContainsString('"priceCurrency":"PEN"', $normalized);
    }
}
