<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La sección "GALERÍA — Descubre la belleza del Perú" existe en producción
 * (limaamericatours.com, sección "Nuestra Galería") pero faltaba en esta app:
 * el home no tenía ninguna tira de fotos de destinos. Este test confirma que
 * la sección se renderiza en `/es` con su título y al menos 5 fotos.
 *
 * Antes del fix: falla (la sección no existe en home.blade.php).
 * Después del fix: pasa (sección + imágenes con fallback siempre presentes).
 */
class HomeGallerySectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_gallery_section_with_title(): void
    {
        $response = $this->get('/es');

        $response->assertOk();
        $response->assertSee('Descubre la belleza del Perú');
    }

    public function test_home_gallery_section_has_at_least_five_images(): void
    {
        $response = $this->get('/es');

        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'lat-gallery__strip');
        $this->assertNotFalse($start, 'No se encontró el contenedor .lat-gallery__strip en el home.');

        $sectionEnd = strpos($html, '</section>', $start);
        $stripHtml = substr($html, $start, ($sectionEnd ?: $start + 6000) - $start);

        $imgCount = substr_count($stripHtml, '<img');

        $this->assertGreaterThanOrEqual(5, $imgCount, "Se esperaban al menos 5 <img> en la galería, se encontraron {$imgCount}.");
    }

    public function test_home_gallery_images_resolve_without_404(): void
    {
        $response = $this->get('/es');
        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'lat-gallery__strip');
        $this->assertNotFalse($start);
        $sectionEnd = strpos($html, '</section>', $start);
        $stripHtml = substr($html, $start, ($sectionEnd ?: $start + 6000) - $start);

        preg_match_all('/<img[^>]+src="([^"]+)"/', $stripHtml, $matches);
        $this->assertNotEmpty($matches[1] ?? [], 'No se encontraron atributos src en las imágenes de la galería.');

        foreach ($matches[1] as $src) {
            // Las imágenes de fallback son locales (asset('storage/...') o public/assets);
            // basta con comprobar que el archivo referenciado existe físicamente.
            $path = parse_url($src, PHP_URL_PATH);
            $localPath = public_path(ltrim($path, '/'));
            $this->assertFileExists($localPath, "Imagen de galería no resuelve en disco: {$src}");
        }
    }
}
