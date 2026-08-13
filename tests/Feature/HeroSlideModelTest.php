<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Capa de datos del slider del hero (mockup 01-home.jpeg: 4 puntos + flechas
 * ←→, docs/rebrand/ESTADO.md "Faltante de alcance, no defecto"). `HeroSlide`
 * es el modelo administrable desde Filament que alimenta
 * App\Services\HeroSlidesResolver — ver HeroSlidesResolverTest para el
 * contrato de `$heroSlides` en sí.
 */
class HeroSlideModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_slides_table_starts_empty(): void
    {
        $this->assertSame(0, HeroSlide::count());
    }

    public function test_active_scope_only_returns_active_slides(): void
    {
        HeroSlide::factory()->create(['alt_es' => 'QA_ activa', 'is_active' => true]);
        HeroSlide::factory()->create(['alt_es' => 'QA_ inactiva', 'is_active' => false]);

        $active = HeroSlide::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('QA_ activa', $active->first()->alt_es);
    }

    /**
     * Orden EXPLÍCITO (columna `order`), no por id de creación — el admin
     * tiene que poder reordenar diapositivas desde el panel sin recrearlas.
     */
    public function test_ordered_scope_respects_the_manual_order_column_not_creation_order(): void
    {
        HeroSlide::factory()->create(['alt_es' => 'QA_ Segunda', 'order' => 2]);
        HeroSlide::factory()->create(['alt_es' => 'QA_ Primera', 'order' => 1]);

        $ordered = HeroSlide::ordered()->get();

        $this->assertSame('QA_ Primera', $ordered->first()->alt_es);
        $this->assertSame('QA_ Segunda', $ordered->last()->alt_es);
    }

    /**
     * Cada diapositiva necesita alt en ES/EN/PT, con el mismo criterio de
     * fallback a español que Guide::role / Guide::bio / Offer::title.
     */
    public function test_alt_accessor_falls_back_to_spanish(): void
    {
        $slide = HeroSlide::factory()->create([
            'alt_es' => 'Texto alternativo en español',
            'alt_en' => null,
        ]);

        app()->setLocale('en');
        $this->assertSame('Texto alternativo en español', $slide->alt);
        app()->setLocale('es');
    }

    public function test_alt_accessor_uses_the_locale_specific_text_when_present(): void
    {
        $slide = HeroSlide::factory()->create([
            'alt_es' => 'Texto en español',
            'alt_en' => 'Text in English',
        ]);

        app()->setLocale('en');
        $this->assertSame('Text in English', $slide->alt);
        app()->setLocale('es');
    }
}
