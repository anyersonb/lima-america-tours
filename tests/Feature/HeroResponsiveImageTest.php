<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La foto del hero es el LCP de todo el sitio. Antes de este fix se servía
 * el archivo tal cual venía (el fallback es un PNG de 2.52 MB) con un único
 * `src`, sin `srcset`, sin variante WebP y sin `preload` — un móvil de 375px
 * descargaba los 2.5 MB completos para pintarlos en 375 px de ancho.
 *
 * Este test fija el contrato de la imagen del hero:
 *   1) Se sirve WebP, no el original.
 *   2) Hay `srcset` con varios anchos + `sizes`, y cada candidato existe en disco.
 *   3) El candidato más grande pesa menos de 300 KB.
 *   4) `width`/`height` son los reales del archivo servido (sin CLS).
 *   5) Carga prioritaria: `fetchpriority="high"`, `loading="eager"`, nunca lazy.
 *   6) Hay `<link rel="preload" as="image">` con imagesrcset/imagesizes.
 *
 * Antes del fix: falla (no hay srcset, ni WebP, ni preload).
 * Después del fix: pasa.
 */
class HeroResponsiveImageTest extends TestCase
{
    use RefreshDatabase;

    /** Devuelve la etiqueta <img> del hero (la que está dentro de .lat-hero__media). */
    private function heroImgTag(string $html): string
    {
        $mediaPos = strpos($html, 'lat-hero__media');
        $this->assertNotFalse($mediaPos, 'No se encontró .lat-hero__media en el home.');

        $imgPos = strpos($html, '<img', $mediaPos);
        $this->assertNotFalse($imgPos, 'No se encontró ningún <img> dentro de .lat-hero__media.');

        $end = strpos($html, '>', $imgPos);

        return substr($html, $imgPos, $end - $imgPos + 1);
    }

    /** Convierte una URL servida en la ruta física dentro de public/. */
    private function localPath(string $url): string
    {
        return public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/'));
    }

    public function test_hero_image_is_served_as_webp(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        preg_match('/\ssrc="([^"]+)"/', $img, $m);
        $this->assertNotEmpty($m[1] ?? '', "El <img> del hero no tiene src: {$img}");

        $path = (string) parse_url($m[1], PHP_URL_PATH);
        $this->assertStringEndsWith('.webp', $path, "El hero debe servirse en WebP, se sirvió: {$path}");
    }

    public function test_hero_image_has_srcset_with_several_widths_and_sizes(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        preg_match('/\ssrcset="([^"]+)"/', $img, $m);
        $srcset = $m[1] ?? '';
        $this->assertNotEmpty($srcset, "El <img> del hero no tiene srcset: {$img}");

        $candidates = array_filter(array_map('trim', explode(',', $srcset)));
        $this->assertGreaterThanOrEqual(
            2,
            count($candidates),
            'El srcset del hero debe ofrecer al menos 2 anchos para que un móvil no baje la foto de escritorio.'
        );

        foreach ($candidates as $candidate) {
            $this->assertMatchesRegularExpression(
                '/^\S+\.webp \d+w$/',
                $candidate,
                "Candidato de srcset mal formado (se espera 'url.webp 640w'): {$candidate}"
            );
        }

        $this->assertMatchesRegularExpression('/\ssizes="[^"]+"/', $img, 'Falta el atributo sizes en el <img> del hero.');
    }

    public function test_every_srcset_candidate_exists_on_disk_and_is_light(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        preg_match('/\ssrcset="([^"]+)"/', $img, $m);
        $srcset = $m[1] ?? '';
        $this->assertNotEmpty($srcset, 'Sin srcset no hay nada que verificar en disco.');

        $heaviest = 0;

        foreach (array_filter(array_map('trim', explode(',', $srcset))) as $candidate) {
            [$url] = explode(' ', $candidate);
            $local = $this->localPath($url);

            $this->assertFileExists($local, "Candidato de srcset que no existe en disco: {$url}");
            $heaviest = max($heaviest, (int) filesize($local));
        }

        $this->assertLessThan(
            300 * 1024,
            $heaviest,
            'La variante más pesada del hero supera los 300 KB: '.round($heaviest / 1024).' KB. Es el LCP del sitio.'
        );
    }

    public function test_hero_image_declares_the_real_dimensions_of_the_file_it_serves(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        preg_match('/\ssrc="([^"]+)"/', $img, $srcMatch);
        preg_match('/\swidth="(\d+)"/', $img, $wMatch);
        preg_match('/\sheight="(\d+)"/', $img, $hMatch);

        $this->assertNotEmpty($wMatch[1] ?? '', 'El hero necesita width explícito para no provocar salto de layout.');
        $this->assertNotEmpty($hMatch[1] ?? '', 'El hero necesita height explícito para no provocar salto de layout.');

        $real = @getimagesize($this->localPath($srcMatch[1]));
        $this->assertNotFalse($real, 'No se pudo leer el tamaño real del archivo servido en el hero.');

        $this->assertSame((int) $real[0], (int) $wMatch[1], 'El width declarado no es el real del archivo servido.');
        $this->assertSame((int) $real[1], (int) $hMatch[1], 'El height declarado no es el real del archivo servido.');
    }

    public function test_hero_image_loads_with_priority_and_never_lazy(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        $this->assertStringContainsString('fetchpriority="high"', $img);
        $this->assertStringContainsString('loading="eager"', $img);
        $this->assertStringNotContainsString('loading="lazy"', $img);
    }

    public function test_head_preloads_the_hero_image_with_the_same_srcset(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $head = substr($html, 0, (int) strpos($html, '</head>'));

        preg_match('/<link[^>]*rel="preload"[^>]*as="image"[^>]*>/', $head, $m);
        $this->assertNotEmpty($m[0] ?? '', 'Falta <link rel="preload" as="image"> del hero en el <head>.');

        $link = $m[0];
        $this->assertStringContainsString('imagesrcset=', $link, 'El preload debe llevar imagesrcset para no precargar el ancho equivocado.');
        $this->assertStringContainsString('imagesizes=', $link, 'El preload debe llevar imagesizes coherente con el sizes del <img>.');

        // El preload y el <img> deben apuntar al mismo juego de candidatos: si
        // difieren, el navegador descarga DOS imágenes de hero.
        $img = $this->heroImgTag($html);
        preg_match('/\ssrcset="([^"]+)"/', $img, $imgSrcset);
        preg_match('/imagesrcset="([^"]+)"/', $link, $linkSrcset);

        $this->assertSame(
            $imgSrcset[1] ?? 'a',
            $linkSrcset[1] ?? 'b',
            'El imagesrcset del preload no coincide con el srcset del <img>: el navegador bajaría la foto dos veces.'
        );
    }
}
