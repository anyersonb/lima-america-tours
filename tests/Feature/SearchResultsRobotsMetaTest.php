<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO S-03 (08-seo.md): /{locale}/buscar recibe q/destino/fecha/pax desde el
 * buscador del hero, generando URLs con parámetros ilimitadas enlazadas desde
 * la home. La vista no emitía ningún <meta name="robots"> propio y dependía
 * solo de robots.txt, que además no bloqueaba /pt/buscar.
 *
 * Fix: tours/results.blade.php declara @section('robots', 'noindex,follow')
 * (sigue enlaces internos a los tours, pero no indexa la página de
 * resultados en sí) y robots.txt deja de bloquear /buscar (combinar
 * Disallow con noindex impide que Google vea el noindex).
 *
 * Este test cubre los 3 locales — la brecha original era justamente que
 * "pt" no tenía ninguna protección.
 */
class SearchResultsRobotsMetaTest extends TestCase
{
    use RefreshDatabase;

    public static function localeProvider(): array
    {
        return [
            'es' => ['es'],
            'en' => ['en'],
            'pt' => ['pt'],
        ];
    }

    /** @dataProvider localeProvider */
    public function test_search_results_has_noindex_follow_robots_meta(string $locale): void
    {
        $response = $this->get(route('tours.results', ['locale' => $locale, 'q' => 'lima']));

        $response->assertOk();
        $html = $response->getContent();

        $count = substr_count($html, '<meta name="robots"');
        $this->assertSame(1, $count, "expected exactly 1 <meta name=\"robots\"> tag, found {$count}");
        $this->assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
    }

    public function test_home_does_not_carry_the_search_results_noindex_follow(): void
    {
        // Guardrail: noindex,follow es específico de /buscar. La home no debe
        // heredarlo por accidente (p.ej. un @section('robots') mal ubicado).
        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $this->assertStringNotContainsString(
            '<meta name="robots" content="noindex,follow">',
            $response->getContent()
        );
    }
}
