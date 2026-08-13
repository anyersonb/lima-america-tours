<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El subtítulo del hero no puede afirmar una antigüedad que el sitio no sostiene.
 *
 * Hasta el 2026-08-12 su default en código decía "10 años mostrando lo mejor del
 * Perú" mientras el badge de años de al lado estaba oculto por no existir
 * `company_started_year`. El sitio se contradecía consigo mismo a 40 píxeles de
 * distancia, y encima el texto se publicaba justo cuando nadie había cargado nada.
 *
 * Regla que fija este test: la cifra de años sale de una sola fuente
 * (`HomeStatsResolver`), así que o aparece en los dos lugares o en ninguno.
 */
class HeroTaglineYearsCoherenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_anio_de_fundacion_el_subtitulo_no_afirma_ninguna_antiguedad(): void
    {
        Setting::set('company_started_year', '');
        Setting::set('home_hero_tagline_es', '');

        $html = $this->get('/es')->assertOk()->getContent();

        $hero = $this->heroHtml($html);

        // Ninguna forma de "N años" dentro del hero.
        $this->assertDoesNotMatchRegularExpression(
            '/\d+\s*\+?\s*años/iu',
            $hero,
            'El hero afirma una antigüedad sin que exista company_started_year.'
        );
        $this->assertStringContainsString('lo mejor del Perú', $hero);
    }

    public function test_con_anio_de_fundacion_la_cifra_del_subtitulo_coincide_con_la_del_badge(): void
    {
        $inicio = (int) now()->format('Y') - 7;
        Setting::set('company_started_year', (string) $inicio);
        Setting::set('home_hero_tagline_es', '');

        $html = $this->get('/es')->assertOk()->getContent();
        $hero = $this->heroHtml($html);

        // El resolver publica "7+" y el subtítulo tiene que decir lo mismo, no otro número.
        $this->assertStringContainsString('7+ años', $hero);
        $this->assertDoesNotMatchRegularExpression('/\b10\s*\+?\s*años/iu', $hero);
    }

    public function test_el_texto_del_panel_sigue_mandando_por_encima_del_default(): void
    {
        Setting::set('company_started_year', '');
        Setting::set('home_hero_tagline_es', "Viajes que se recuerdan\npor toda la vida");

        $hero = $this->heroHtml($this->get('/es')->assertOk()->getContent());

        $this->assertStringContainsString('Viajes que se recuerdan', $hero);
    }

    /**
     * Recorta al hero: "años" aparece legítimamente en otras partes de la página
     * (la barra de stats, el pie), y comparar contra todo el HTML daría un falso
     * positivo — el error clásico de este proyecto.
     */
    private function heroHtml(string $html): string
    {
        if (preg_match('/<section[^>]*class="[^"]*lat-hero\b.*?<\/section>/is', $html, $m)) {
            return $m[0];
        }

        // Si el marcado del hero cambia, el test debe fallar en vez de medir la página
        // entera y dar un veredicto que no significa nada.
        $this->fail('No se pudo aislar el <section> del hero: revisar el selector antes de confiar en este test.');
    }
}
