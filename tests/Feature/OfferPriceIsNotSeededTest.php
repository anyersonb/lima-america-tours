<?php

namespace Tests\Feature;

use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las tarjetas de promoción del hero no pueden publicar un precio inventado.
 *
 * Hallazgo del 2026-08-12: las 3 ofertas activas mostraban "Desde $200" — el
 * mismo importe en las tres, incluidas una promo de 10% de descuento y un combo,
 * que no tienen un "desde" propio. El 200 venía de `OfferSeeder`, no de la
 * clienta. Publicar tres precios idénticos e inventados es peor que no mostrar
 * precio: le pone un número falso al catálogo.
 */
class OfferPriceIsNotSeededTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_no_siembra_precios_en_las_ofertas(): void
    {
        $this->seed(\Database\Seeders\OfferSeeder::class);

        $conPrecio = Offer::whereNotNull('price')->pluck('title_es')->all();

        $this->assertSame([], $conPrecio, 'El seeder está sembrando precios en ofertas: '.implode(', ', $conPrecio));
    }

    public function test_las_promos_del_home_no_repiten_todas_el_mismo_precio(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $precios = Offer::where('is_active', true)->whereNotNull('price')->pluck('price')->all();

        // Que haya precios no es el problema; que TODOS sean el mismo delata un
        // valor de relleno. Con 0 o 1 precio cargado no hay nada que comparar.
        if (count($precios) < 2) {
            $this->assertTrue(true);

            return;
        }

        $this->assertGreaterThan(
            1,
            count(array_unique($precios)),
            'Las '.count($precios).' promos activas publican el mismo precio ('.$precios[0].'): tiene pinta de dato de relleno, no de precio real.'
        );
    }

    public function test_la_home_no_pinta_el_bloque_desde_cuando_la_oferta_no_tiene_precio(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        Offer::query()->update(['price' => null]);

        $html = $this->get('/es')->assertOk()->getContent();

        // Se recorta al bloque de promos del hero: "Desde" también aparece en las
        // tarjetas de tour, que sí tienen precio real, y compararlo contra todo el
        // HTML daría un falso positivo.
        if (preg_match('#lat-hero__promos.*?</div>\s*</div>#s', $html, $m)) {
            $this->assertStringNotContainsString('lat-promo__price', $m[0]);
        } else {
            $this->assertStringNotContainsString('lat-promo__price', $html);
        }
    }
}
