<?php

namespace Tests\Feature;

use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El botón "Leer más" de las tarjetas de promoción tiene que caer DENTRO de
 * esta aplicación.
 *
 * Hallazgo del 2026-08-24 (reportado por el jefe con la captura del 404): las
 * tres tarjetas del home llevaban a "No se ha podido encontrar la página" del
 * WordPress viejo. `OfferSeeder` guardaba `cta_url = '/es/tours'`, una ruta
 * absoluta DESDE LA RAÍZ DEL DOMINIO, y esta app no vive en el docroot: en
 * staging cuelga de `/staging`, así que el navegador pedía
 * `limaamericatours.com/es/tours` — WordPress, no Laravel.
 *
 * Dos cosas distintas se vigilan acá:
 *  1. Que el seeder no vuelva a sembrar una ruta de ese tipo.
 *  2. Que `Offer::ctaHref()` respete la base de la instalación aunque alguien
 *     escriba una ruta así a mano en el panel. Lo primero es el dato de hoy;
 *     lo segundo es lo que impide que el defecto vuelva por otra puerta.
 */
class OfferCtaUrlIsNotRootRelativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_no_siembra_rutas_relativas_a_la_raiz_del_dominio(): void
    {
        $this->seed(\Database\Seeders\OfferSeeder::class);

        $malas = Offer::whereNotNull('cta_url')
            ->where('cta_url', 'like', '/%')
            ->pluck('cta_url')
            ->all();

        $this->assertSame(
            [],
            $malas,
            'El seeder siembra rutas absolutas desde la raíz del dominio: '.implode(', ', $malas)
                .'. Fuera del docroot apuntan al sitio de al lado, no a esta app.'
        );
    }

    public function test_sin_cta_url_el_boton_lleva_al_catalogo_del_idioma_que_se_esta_viendo(): void
    {
        $offer = Offer::create([
            'title_es' => 'Promo de prueba','cta_url' => null, 'tour_id' => null]);

        $this->assertSame(route('tours.index', ['locale' => 'en']), $offer->ctaHref('en'));
        $this->assertSame(route('tours.index', ['locale' => 'pt']), $offer->ctaHref('pt'));
    }

    public function test_una_ruta_escrita_a_mano_se_resuelve_sobre_la_base_de_la_instalacion(): void
    {
        // Simula el staging real: la app colgando de una subcarpeta, no del
        // docroot. Sin `url()`, "/es/tours" saldría de la instalación.
        \Illuminate\Support\Facades\URL::forceRootUrl('https://limaamericatours.com/staging');

        $offer = Offer::create([
            'title_es' => 'Promo de prueba','cta_url' => '/es/tours', 'tour_id' => null]);

        // El esquema lo fuerza el entorno de test; lo que importa acá es que la
        // SUBCARPETA de la instalación sobreviva.
        $this->assertSame(
            '//limaamericatours.com/staging/es/tours',
            preg_replace('#^https?:#', '', $offer->ctaHref('es'))
        );
    }

    public function test_una_url_absoluta_se_respeta_tal_cual(): void
    {
        $offer = Offer::create([
            'title_es' => 'Promo de prueba',
            'cta_url' => 'https://wa.me/51957299438',
            'tour_id' => null,
        ]);

        $this->assertSame('https://wa.me/51957299438', $offer->ctaHref('es'));
    }

    public function test_el_home_no_publica_ningun_enlace_de_promo_fuera_de_la_instalacion(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $html = $this->get('/es')->assertOk()->getContent();

        if (! preg_match('#lat-hero__promos(.*?)</article>\s*</div>#s', $html, $m)) {
            $this->markTestSkipped('No hay promos activas en el home para verificar.');
        }

        preg_match_all('#href="([^"]+)"#', $m[1], $hrefs);

        foreach ($hrefs[1] as $href) {
            $this->assertStringStartsWith(
                url('/'),
                $href,
                "El enlace de promo $href sale de la instalación: fuera del docroot cae en el sitio de al lado."
            );
        }
    }
}
