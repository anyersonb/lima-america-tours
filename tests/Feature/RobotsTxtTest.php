<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO S-03/S-05 (08-seo.md): robots.txt bloqueaba /buscar, /es/buscar y
 * /en/buscar (pero no /pt/buscar) con Disallow. Combinar Disallow con el
 * noindex,follow que ahora emite tours/results.blade.php es el antipatrón
 * que Google desaconseja: si no puede rastrear la URL, tampoco puede leer
 * su <meta name="robots">. El mecanismo correcto es solo el noindex,follow
 * de la vista (cubierto por SearchResultsRobotsMetaTest); robots.txt ya no
 * debe mencionar /buscar en absoluto.
 */
class RobotsTxtTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_robots_txt_does_not_disallow_buscar(): void
    {
        // SitemapController::robots() ramifica sobre config('app.env'), no sobre
        // app()->environment(): asignar $this->app['env'] deja el config intacto y
        // el test evaluaba la rama de NO producción, cuyo cuerpo es solo
        // "Disallow: /" — ahí "no contiene /buscar" pasaba por vacío.
        config(['app.env' => 'production']);

        $content = $this->get('/robots.txt')->assertOk()->getContent();

        // Guarda de que estamos leyendo de verdad la rama de producción.
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringNotContainsString('/buscar', $content);
    }

    /**
     * El checkout se bloqueaba en /checkout, /es/checkout y /en/checkout, pero
     * el prefijo PT se agregó al sitio después y nadie actualizó estas listas
     * (mismo descuido que tenía /buscar). El bloqueo debe cubrir los tres
     * idiomas, y también dentro del bloque de bots de IA.
     */
    public function test_production_robots_txt_disallows_checkout_in_every_locale(): void
    {
        config(['app.env' => 'production']);

        $content = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/checkout', '/es/checkout', '/en/checkout', '/pt/checkout'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path."\n", $content, $path.' no está bloqueado en robots.txt');
        }

        // Una vez por el bloque genérico + una por cada agente de IA listado.
        $aiBotCount = substr_count($content, 'Allow: /') - 1;
        $this->assertSame(
            $aiBotCount + 1,
            substr_count($content, 'Disallow: /pt/checkout'),
            'Los bots de IA también deben tener bloqueado /pt/checkout'
        );
    }

    public function test_sitemap_does_not_list_the_search_results_route(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringNotContainsString('/buscar', $response->getContent());
    }
}
