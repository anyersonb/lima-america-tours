<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guardarraíl de peso de la home.
 *
 * El hero ya se servía optimizado y aun así el navegador descargaba 2522 KB en
 * una sola petición: el PNG viejo de 2.52 MB seguía usado como fondo del footer
 * y como relleno de las tarjetas de categoría y de ofertas. Medido con
 * PerformanceObserver, no deducido del código — por eso este test existe: un
 * archivo pesado puede volver a entrar por una vía que nadie está mirando.
 *
 * Regla: ninguna imagen que SIRVA EL PROYECTO (public/assets + las variantes
 * generadas en public/media) puede pasar de 300 KB en la home.
 *
 * ALCANCE DELIBERADO: quedan fuera las fotos de CONTENIDO (portadas de tour en
 * storage/app/public/tours, subidas o importadas de WordPress). No porque no
 * pesen — medido en el navegador, la portada `2024-10-IMG_9471-scaled.jpeg` son
 * 303 KB y `2025-10-caption-4-1.jpg` 204 KB — sino porque son datos del cliente:
 * un test que falla por una foto que subió la clienta bloquea la suite sin que
 * haya un bug de código. Esa deuda va reportada aparte (las subidas NUEVAS ya
 * pasan por ImageOptimizer; las importadas del WP viejo no lo hicieron).
 */
class HomeImageWeightBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const BUDGET_KB = 300;

    public function test_no_local_image_referenced_by_the_home_exceeds_the_budget(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        // src, srcset y url(...) de estilos inline (el fondo del footer entra por ahí).
        preg_match_all('/(?:src|srcset|href)="([^"]+)"|url\(([^)]+)\)/i', $html, $matches);

        $candidates = array_merge($matches[1] ?? [], $matches[2] ?? []);
        $heavy = [];
        $checked = 0;

        foreach ($candidates as $candidate) {
            foreach (explode(',', $candidate) as $part) {
                $url = trim(explode(' ', trim($part, " '\""))[0]);

                if (! preg_match('/\.(jpe?g|png|webp|avif|gif)$/i', $url)) {
                    continue;
                }

                $path = (string) parse_url($url, PHP_URL_PATH);

                // Solo lo que sirve el proyecto: assets del repo y variantes
                // generadas. `/storage/...` es contenido del cliente (ver el
                // docblock) y no entra en este presupuesto.
                if (! preg_match('#^/(assets|media)/#', $path)) {
                    continue;
                }

                $local = public_path(ltrim($path, '/'));

                if (! is_file($local)) {
                    continue;
                }

                $checked++;
                $kb = (int) round(filesize($local) / 1024);

                if ($kb > self::BUDGET_KB) {
                    $heavy[basename($local)] = $kb.' KB';
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'No se encontró ninguna imagen local en la home: el test no está midiendo nada.');

        $this->assertSame(
            [],
            $heavy,
            'Imágenes locales de la home por encima de '.self::BUDGET_KB." KB:\n".json_encode($heavy, JSON_PRETTY_PRINT)
        );
    }

    public function test_the_retired_two_and_a_half_megabyte_png_is_gone(): void
    {
        $this->assertFileDoesNotExist(
            public_path('assets/banners/hero-machu-picchu.png'),
            'Volvió el PNG de 2.52 MB. Los rellenos deben pasar por ResponsiveImage::defaultPhotoUrl().'
        );
    }
}
