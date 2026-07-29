<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cambio pedido el 2026-07-29: la foto del hero pasa a ser la panorámica de
 * Machu Picchu (1920×960) que mandó el jefe, y el hero debe cubrir al menos
 * 80vh en escritorio.
 *
 * Al cambiar la foto aparece un problema que no existía antes: el `alt` estaba
 * escrito a mano describiendo el malecón de Miraflores. Un alt fijo sobre una
 * imagen editable desde el CMS acaba mintiendo, así que el alt también pasa a
 * ser un campo del panel.
 *
 * Antes del fix: falla (sirve el PNG viejo, alt del faro, sin 80vh).
 * Después del fix: pasa.
 */
class HeroPhotoAndHeightTest extends TestCase
{
    use RefreshDatabase;

    private function heroImgTag(string $html): string
    {
        $mediaPos = strpos($html, 'lat-hero__media');
        $imgPos = strpos($html, '<img', (int) $mediaPos);

        return substr($html, (int) $imgPos, strpos($html, '>', (int) $imgPos) - $imgPos + 1);
    }

    public function test_default_hero_photo_is_the_panoramic_file_and_exists(): void
    {
        $path = public_path('assets/banners/hero-machu-picchu-pano.jpg');

        $this->assertFileExists($path, 'Falta la foto panorámica del hero en public/assets/banners.');

        $size = getimagesize($path);
        $this->assertSame(1920, $size[0]);
        $this->assertSame(960, $size[1]);
    }

    public function test_hero_serves_derivatives_of_the_new_photo(): void
    {
        $img = $this->heroImgTag($this->get('/es')->assertOk()->getContent());

        preg_match('/\ssrcset="([^"]+)"/', $img, $m);
        $srcset = $m[1] ?? '';

        $this->assertStringContainsString('hero-machu-picchu-pano', $srcset, "El hero no está sirviendo la foto nueva: {$srcset}");

        // 640 para móvil y 1600 como techo (ver el comentario de HERO_WIDTHS:
        // a 1920 esta foto no entra en el presupuesto del LCP).
        $this->assertStringContainsString('640w', $srcset);
        $this->assertStringContainsString('1600w', $srcset);
        $this->assertStringNotContainsString('1920w', $srcset, 'El techo del hero es 1600: a 1920 la foto supera el presupuesto de peso.');
    }

    public function test_hero_alt_describes_the_new_photo_and_is_editable(): void
    {
        $img = $this->heroImgTag($this->get('/es')->assertOk()->getContent());
        preg_match('/\salt="([^"]*)"/', $img, $m);

        $this->assertStringContainsString('Machu Picchu', $m[1] ?? '', 'El alt sigue describiendo la foto anterior.');

        Setting::set('home_hero_image_alt_es', 'Vista del valle del Colca al amanecer');

        $img = $this->heroImgTag($this->get('/es')->assertOk()->getContent());
        preg_match('/\salt="([^"]*)"/', $img, $m);

        $this->assertSame('Vista del valle del Colca al amanecer', $m[1] ?? '', 'El alt del hero no es editable desde el CMS.');
    }

    public function test_hero_is_at_least_80vh_on_desktop(): void
    {
        $scss = (string) file_get_contents(base_path('resources/scss/pages/_lat-home.scss'));

        $start = strpos($scss, '.lat-hero {');
        $this->assertNotFalse($start);

        $block = substr($scss, $start, 900);

        $this->assertMatchesRegularExpression(
            '/min-height:\s*(8[0-9]|9[0-9]|100)vh/',
            $block,
            'El hero ya no declara una altura mínima de al menos 80vh en escritorio.'
        );
    }

    public function test_largest_derivative_stays_under_the_weight_budget(): void
    {
        $img = $this->heroImgTag($this->get('/es')->assertOk()->getContent());
        preg_match('/\ssrcset="([^"]+)"/', $img, $m);

        $heaviest = 0;

        foreach (array_filter(array_map('trim', explode(',', $m[1] ?? ''))) as $candidate) {
            [$url] = explode(' ', $candidate);
            $local = public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/'));
            $this->assertFileExists($local);
            $heaviest = max($heaviest, (int) filesize($local));
        }

        $this->assertLessThan(
            300 * 1024,
            $heaviest,
            'La variante más pesada del hero pasó de 300 KB: '.round($heaviest / 1024).' KB.'
        );
    }
}
