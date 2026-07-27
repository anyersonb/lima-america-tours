<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Objetivo: en entornos NO producción (staging/local/testing), TODAS las
 * páginas públicas deben salir noindex — antes de este fix solo 404 y
 * checkout lo tenían; home/tours/ficha/blog/nosotros/contacto salían
 * "index,follow" incluso en staging, arriesgando que Google las indexe
 * antes del lanzamiento oficial.
 *
 * Cubre las dos capas del fix:
 *  1. App\Http\Middleware\StagingNoindex — header X-Robots-Tag en TODA
 *     respuesta HTTP (global middleware, no solo el grupo 'web').
 *  2. resources/views/layouts/app.blade.php — <meta name="robots"> visible
 *     en el HTML, para gestores/auditores que no miran headers.
 *
 * Producción no debe verse afectada: se simula forzando el entorno de la
 * aplicación a 'production' dentro del test (Application::environment()
 * lee $this->app['env'], que es seguro sobreescribir en un test aislado).
 */
class StagingNoindexTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_forces_noindex_header_and_meta_in_non_production_env(): void
    {
        // phpunit.xml fija APP_ENV=testing, que NO es 'production'.
        $this->assertFalse(app()->environment('production'));

        $response = $this->get('/'.self::LOCALE);

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag');
        $this->assertStringContainsString(
            'noindex',
            $response->headers->get('X-Robots-Tag'),
            'X-Robots-Tag debe contener "noindex" en entornos no productivos'
        );

        $html = $response->getContent();
        $this->assertStringContainsString('<meta name="robots"', $html);
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex,nofollow">',
            $html,
            'El <meta name="robots"> visible debe forzar noindex,nofollow fuera de producción'
        );
    }

    public function test_home_does_not_force_noindex_when_environment_is_production(): void
    {
        // Simula producción sobreescribiendo el binding 'env' del contenedor,
        // que es exactamente lo que Application::environment() consulta.
        $this->app['env'] = 'production';
        $this->assertTrue(app()->environment('production'));

        $response = $this->get('/'.self::LOCALE);

        $response->assertOk();
        $this->assertFalse(
            $response->headers->has('X-Robots-Tag'),
            'En producción el middleware StagingNoindex no debe añadir X-Robots-Tag'
        );

        $html = $response->getContent();
        $this->assertStringNotContainsString(
            '<meta name="robots" content="noindex,nofollow">',
            $html,
            'En producción el meta robots no debe forzar noindex,nofollow'
        );
        $this->assertStringContainsString(
            '<meta name="robots" content="index,follow',
            $html
        );
    }
}
