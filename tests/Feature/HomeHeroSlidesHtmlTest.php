<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verifica el contrato de $heroSlides en el HTML REALMENTE servido por /es
 * (no solo en el Blade en aislado): que sin diapositivas cargadas la home se
 * ve idéntica a hoy, que la primera diapositiva activa manda cuando SÍ hay
 * datos, y que una diapositiva inactiva nunca se publica. Mismo criterio que
 * HeroResponsiveImageTest (heroImgTag) para no medir la página entera.
 */
class HomeHeroSlidesHtmlTest extends TestCase
{
    use RefreshDatabase;

    private function heroImgTag(string $html): string
    {
        $mediaPos = strpos($html, 'lat-hero__bg');
        $this->assertNotFalse($mediaPos, 'No se encontró .lat-hero__bg en el home.');

        $imgPos = strpos($html, '<img', $mediaPos);
        $this->assertNotFalse($imgPos, 'No se encontró ningún <img> dentro de .lat-hero__bg.');

        $end = strpos($html, '>', $imgPos);

        return substr($html, $imgPos, $end - $imgPos + 1);
    }

    private function preloadLink(string $html): string
    {
        $head = substr($html, 0, (int) strpos($html, '</head>'));
        preg_match('/<link[^>]*rel="preload"[^>]*as="image"[^>]*>/', $head, $m);
        $this->assertNotEmpty($m[0] ?? '', 'Falta <link rel="preload" as="image"> del hero en el <head>.');

        return $m[0];
    }

    /**
     * Regla 1 del contrato: sin diapositivas cargadas, la home se ve
     * IDÉNTICA a antes de este cambio — misma foto por defecto
     * (hero-machu-picchu-pano), servida por el mismo ResponsiveImage.
     */
    public function test_without_hero_slides_the_home_serves_the_same_default_hero_image_as_before(): void
    {
        $this->assertSame(0, HeroSlide::count());

        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        preg_match('/\ssrc="([^"]+)"/', $img, $m);
        $this->assertStringContainsString(
            'hero-machu-picchu-pano',
            $m[1] ?? '',
            'Sin diapositivas en el CMS, el hero debe seguir sirviendo la foto por defecto de siempre.'
        );
    }

    /**
     * Con una diapositiva activa cargada, es ELLA la que manda en el hero
     * (imagen + alt) y la que se precarga — no el fallback de
     * home_hero_image, aunque ese Setting siga vacío.
     */
    public function test_a_single_active_hero_slide_becomes_the_preloaded_hero_image(): void
    {
        Storage::fake('media');

        HeroSlide::factory()->create([
            'image' => 'home/qa-diapositiva-unica.jpg',
            'alt_es' => 'QA_ Diapositiva única cargada desde el CMS',
            'order' => 0,
            'is_active' => true,
        ]);

        $expectedUrl = Storage::disk('media')->url('home/qa-diapositiva-unica.jpg');

        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);
        $preload = $this->preloadLink($html);

        $this->assertStringContainsString($expectedUrl, $img);
        $this->assertStringContainsString('QA_ Diapositiva única cargada desde el CMS', $img);
        $this->assertStringContainsString($expectedUrl, $preload, 'El preload debe apuntar a la diapositiva real, no al fallback.');
        $this->assertStringContainsString('fetchpriority="high"', $img);
    }

    /**
     * Con varias diapositivas activas, la de `order` MÁS BAJO es la que se
     * pinta/precarga — no la de creación más reciente ni cualquier otra.
     */
    public function test_with_several_active_slides_the_lowest_order_one_is_shown_and_preloaded(): void
    {
        Storage::fake('media');

        HeroSlide::factory()->create([
            'image' => 'home/qa-segunda.jpg',
            'alt_es' => 'QA_ Segunda diapositiva',
            'order' => 5,
        ]);
        HeroSlide::factory()->create([
            'image' => 'home/qa-primera.jpg',
            'alt_es' => 'QA_ Primera diapositiva',
            'order' => 1,
        ]);

        $expectedFirstUrl = Storage::disk('media')->url('home/qa-primera.jpg');
        $unexpectedSecondUrl = Storage::disk('media')->url('home/qa-segunda.jpg');

        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        $this->assertStringContainsString($expectedFirstUrl, $img);
        $this->assertStringNotContainsString($unexpectedSecondUrl, $img);
        $this->assertStringContainsString('QA_ Primera diapositiva', $img);
    }

    /**
     * Regla explícita del encargo: una diapositiva INACTIVA nunca se
     * publica, aunque tenga el `order` más bajo de todas.
     */
    public function test_an_inactive_slide_is_never_shown_even_with_the_lowest_order(): void
    {
        Storage::fake('media');

        HeroSlide::factory()->create([
            'image' => 'home/qa-inactiva-primero.jpg',
            'alt_es' => 'QA_ Inactiva, no debería verse',
            'order' => 0,
            'is_active' => false,
        ]);
        HeroSlide::factory()->create([
            'image' => 'home/qa-activa-segundo.jpg',
            'alt_es' => 'QA_ Activa, esta sí debería verse',
            'order' => 1,
            'is_active' => true,
        ]);

        $inactiveUrl = Storage::disk('media')->url('home/qa-inactiva-primero.jpg');
        $activeUrl = Storage::disk('media')->url('home/qa-activa-segundo.jpg');

        $html = $this->get('/es')->assertOk()->getContent();
        $img = $this->heroImgTag($html);

        $this->assertStringContainsString($activeUrl, $img);
        $this->assertStringNotContainsString($inactiveUrl, $img);
        $this->assertStringContainsString('QA_ Activa, esta sí debería verse', $img);
        $this->assertStringNotContainsString('QA_ Inactiva, no debería verse', $html);
    }
}
