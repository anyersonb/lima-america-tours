<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Support\ResponsiveImage;
use Database\Seeders\HeroSlideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `HeroSlideSeeder` es lo que hace que una instalación nueva vea el hero
 * IGUAL que hoy (regla 1 del contrato de $heroSlides): una sola diapositiva
 * con la misma foto que ya usa el fallback de `home_hero_image`
 * (ResponsiveImage::DEFAULT_PHOTO — misma fuente, para que las dos rutas
 * nunca puedan divergir). Idempotente por diseño, mismo patrón que
 * GuideSeederTest.
 */
class HeroSlideSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_exactly_one_slide_with_the_current_default_hero_photo(): void
    {
        (new HeroSlideSeeder)->run();

        $this->assertSame(1, HeroSlide::count());
        $this->assertSame(ResponsiveImage::DEFAULT_PHOTO, HeroSlide::first()->image);
    }

    public function test_running_it_twice_does_not_duplicate_rows(): void
    {
        (new HeroSlideSeeder)->run();
        (new HeroSlideSeeder)->run();

        $this->assertSame(1, HeroSlide::count());
    }

    public function test_the_seeded_slide_is_active_and_first_in_order(): void
    {
        (new HeroSlideSeeder)->run();

        $slide = HeroSlide::first();
        $this->assertTrue($slide->is_active);
        $this->assertSame(0, $slide->order);
    }

    public function test_the_seeded_slide_has_alt_text_in_the_three_languages(): void
    {
        (new HeroSlideSeeder)->run();

        $slide = HeroSlide::first();
        $this->assertNotEmpty($slide->alt_es);
        $this->assertNotEmpty($slide->alt_en);
        $this->assertNotEmpty($slide->alt_pt);
    }
}
