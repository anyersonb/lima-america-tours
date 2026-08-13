<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Services\HeroSlidesResolver;
use App\Support\ResponsiveImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fija el contrato de `$heroSlides` que consume resources/views/home.blade.php
 * y del que depende el maquetador para construir el slider real (mockup
 * 01-home.jpeg): una colección ORDENADA, NUNCA vacía, donde cada elemento
 * trae url/srcset/sizes/alt/is_first.
 *
 * El fallback (imagen/alt de `home_hero_image`) se pasa ya resuelto desde
 * home.blade.php — a propósito no se duplica esa lógica acá (ver docblock de
 * HeroSlidesResolver), así que estos tests lo simulan con valores fijos.
 */
class HeroSlidesResolverTest extends TestCase
{
    use RefreshDatabase;

    private function fallbackImage(): array
    {
        return [
            'src' => 'https://example.test/media/derived/fallback-1600.webp',
            'srcset' => 'https://example.test/media/derived/fallback-640.webp 640w, https://example.test/media/derived/fallback-1600.webp 1600w',
            'sizes' => '100vw',
            'width' => 1600,
            'height' => 800,
        ];
    }

    private function resolver(): HeroSlidesResolver
    {
        return app(HeroSlidesResolver::class);
    }

    /**
     * Regla 1 del contrato: sin diapositivas cargadas en el CMS, $heroSlides
     * devuelve EXACTAMENTE la imagen que el hero muestra hoy — nunca vacía.
     */
    public function test_without_active_slides_it_returns_exactly_one_slide_with_the_fallback_image(): void
    {
        $this->assertSame(0, HeroSlide::count());

        $slides = $this->resolver()->resolve('es', $this->fallbackImage(), 'Alt del fallback');

        $this->assertCount(1, $slides);

        $only = $slides->first();
        $this->assertSame($this->fallbackImage()['src'], $only['url']);
        $this->assertSame($this->fallbackImage()['srcset'], $only['srcset']);
        $this->assertSame($this->fallbackImage()['sizes'], $only['sizes']);
        $this->assertSame('Alt del fallback', $only['alt']);
        $this->assertTrue($only['is_first']);
    }

    /**
     * Regla "una diapositiva inactiva no se publique": si la ÚNICA fila que
     * existe está inactiva, sigue sin haber nada publicable — cae en el
     * mismo fallback que una tabla vacía, no en la fila inactiva.
     */
    public function test_an_inactive_slide_alone_still_falls_back_never_publishing_it(): void
    {
        HeroSlide::factory()->create([
            'image' => 'home/qa-inactiva.jpg',
            'alt_es' => 'QA_ no debería verse nunca',
            'is_active' => false,
            'order' => 0,
        ]);

        $slides = $this->resolver()->resolve('es', $this->fallbackImage(), 'Alt del fallback');

        $this->assertCount(1, $slides);
        $only = $slides->first();
        $this->assertSame($this->fallbackImage()['src'], $only['url']);
        $this->assertNotSame('QA_ no debería verse nunca', $only['alt']);
    }

    /**
     * Regla "orden explícito y editable desde el panel, no por id": una
     * diapositiva creada DESPUÉS pero con `order` menor debe salir primero.
     */
    public function test_respects_the_explicit_order_column_not_creation_order(): void
    {
        $createdFirst = HeroSlide::factory()->create([
            'image' => 'home/qa-creada-primero.jpg',
            'alt_es' => 'QA_ creada primero, orden 2',
            'order' => 2,
        ]);
        $createdSecond = HeroSlide::factory()->create([
            'image' => 'home/qa-creada-segundo.jpg',
            'alt_es' => 'QA_ creada segundo, orden 1',
            'order' => 1,
        ]);

        $slides = $this->resolver()->resolve('es', $this->fallbackImage(), 'Alt del fallback')->values();

        $this->assertCount(2, $slides);
        $this->assertSame('QA_ creada segundo, orden 1', $slides[0]['alt'], 'La fila con order=1 debe salir primero aunque se creó después.');
        $this->assertSame('QA_ creada primero, orden 2', $slides[1]['alt']);
    }

    /**
     * Regla 2 del contrato (LCP): la primera diapositiva es la ÚNICA con
     * `is_first = true`. El resto no se precarga.
     */
    public function test_only_the_first_slide_in_order_is_marked_is_first(): void
    {
        HeroSlide::factory()->create(['image' => 'home/qa-a.jpg', 'alt_es' => 'A', 'order' => 0]);
        HeroSlide::factory()->create(['image' => 'home/qa-b.jpg', 'alt_es' => 'B', 'order' => 1]);
        HeroSlide::factory()->create(['image' => 'home/qa-c.jpg', 'alt_es' => 'C', 'order' => 2]);

        $slides = $this->resolver()->resolve('es', $this->fallbackImage(), 'Alt del fallback')->values();

        $this->assertTrue($slides[0]['is_first']);
        $this->assertFalse($slides[1]['is_first']);
        $this->assertFalse($slides[2]['is_first']);
    }

    /**
     * Una diapositiva inactiva mezclada entre activas no debe aparecer en
     * absoluto (ni como fallback ni como slide intermedio).
     */
    public function test_an_inactive_slide_mixed_with_active_ones_is_excluded_entirely(): void
    {
        HeroSlide::factory()->create(['image' => 'home/qa-activa-1.jpg', 'alt_es' => 'QA_ activa 1', 'order' => 0, 'is_active' => true]);
        HeroSlide::factory()->create(['image' => 'home/qa-inactiva.jpg', 'alt_es' => 'QA_ inactiva', 'order' => 1, 'is_active' => false]);
        HeroSlide::factory()->create(['image' => 'home/qa-activa-2.jpg', 'alt_es' => 'QA_ activa 2', 'order' => 2, 'is_active' => true]);

        $slides = $this->resolver()->resolve('es', $this->fallbackImage(), 'Alt del fallback');

        $this->assertCount(2, $slides, 'Debe haber exactamente 2 diapositivas activas, la inactiva no cuenta.');
        $this->assertFalse($slides->pluck('alt')->contains('QA_ inactiva'));
    }

    /**
     * Cada URL pasa por ResponsiveImage — mismas variantes/anchos que hoy
     * usa el hero (no se arruina el LCP). Se usa la propia foto por defecto
     * como archivo real en disco para poder verificar que SÍ hay srcset.
     */
    public function test_each_slide_image_goes_through_responsive_image(): void
    {
        HeroSlide::factory()->create([
            'image' => ResponsiveImage::DEFAULT_PHOTO,
            'alt_es' => 'QA_ con archivo real',
            'order' => 0,
        ]);

        $slide = $this->resolver()->resolve('es', $this->fallbackImage(), 'Alt del fallback')->first();

        $this->assertNotEmpty($slide['srcset'], 'Un archivo real en disco debe producir srcset con variantes WebP.');
        $this->assertStringEndsWith('.webp', (string) parse_url($slide['url'], PHP_URL_PATH));
    }
}
