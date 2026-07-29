<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las 6 fotos de la galería del home se sirven como derivados WebP, no como los
 * JPG/PNG originales importados de WordPress (uno pesa 319 KB y otro 274 KB).
 *
 * El segundo test es el que importa de verdad. La primera versión de este código
 * derivaba la ruta física parseando la URL pública (`public_path(parse_url($url,
 * PHP_URL_PATH))`). En local funcionaba y en STAGING no hacía nada: allí la app
 * vive en una subcarpeta (`/staging`), así que `asset()` devuelve
 * `/staging/storage/...` y esa ruta no existe en el disco — la optimización se
 * caía al fallback en silencio y se servían los originales. Se detectó midiendo
 * el peso en el navegador contra staging, no con los tests.
 *
 * Ahora la URL y la ruta física se construyen por separado, y este test lo
 * blinda simulando la instalación en subcarpeta.
 */
class HomeGalleryDerivativesTest extends TestCase
{
    use RefreshDatabase;

    /** @return string[] src de las imágenes de la galería */
    private function gallerySources(): array
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $start = strpos($html, 'lat-gallery__strip');
        $this->assertNotFalse($start, 'No se encontró la tira de la galería.');

        $end = strpos($html, '</section>', $start);
        $strip = substr($html, $start, ($end ?: $start + 8000) - $start);

        preg_match_all('/<img[^>]+src="([^"]+)"/', $strip, $m);

        $this->assertNotEmpty($m[1] ?? [], 'La galería no tiene imágenes.');

        return $m[1];
    }

    public function test_gallery_images_are_served_as_webp_derivatives(): void
    {
        foreach ($this->gallerySources() as $src) {
            $path = (string) parse_url($src, PHP_URL_PATH);

            $this->assertStringEndsWith('.webp', $path, "Imagen de galería sin optimizar: {$src}");
            $this->assertStringContainsString('/media/derived/', $path, "No es un derivado generado: {$src}");
        }
    }

    public function test_gallery_optimisation_still_works_when_the_app_lives_in_a_subfolder(): void
    {
        // Es como corre staging: https://dominio/staging. El assetRoot se
        // inyecta al construir el UrlGenerator (no hay setter público en
        // Laravel 10), así que se rebindea el servicio con la raíz de la
        // subcarpeta — es lo que hace en el server el APP_URL/ASSET_URL.
        $this->app->instance('url', new \Illuminate\Routing\UrlGenerator(
            $this->app['router']->getRoutes(),
            $this->app['request'],
            'http://localhost/staging'
        ));

        $sources = $this->gallerySources();

        foreach ($sources as $src) {
            $this->assertStringEndsWith(
                '.webp',
                (string) parse_url($src, PHP_URL_PATH),
                "Con la app en subcarpeta la galería dejó de optimizarse: {$src}"
            );
        }
    }

    public function test_gallery_derivatives_exist_on_disk_and_are_light(): void
    {
        foreach ($this->gallerySources() as $src) {
            $local = public_path(ltrim((string) parse_url($src, PHP_URL_PATH), '/'));

            $this->assertFileExists($local, "Derivado de galería que no existe en disco: {$src}");
            $this->assertLessThan(
                150 * 1024,
                filesize($local),
                'Una foto de la galería pasa de 150 KB: '.basename($local).' ('.round(filesize($local) / 1024).' KB)'
            );
        }
    }
}
