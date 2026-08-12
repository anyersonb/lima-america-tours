<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Region;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tarea B/C del lote 2026-08-10: "Explora los destinos del Perú" y
 * "Explora por categoría" solo deben listar destinos/categorías con AL
 * MENOS un tour publicado detrás — nunca un enlace a un listado de 0
 * resultados. El guard es automático (Region::scopeWithPublishedTours /
 * Category::scopeWithPublishedTours): agregar el primer tour publicado de
 * una región/categoría nueva la hace aparecer sola, sin tocar código.
 */
class DestinationsAndCategoriesGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_region_without_any_published_tour_is_excluded(): void
    {
        $arequipa = Region::create(['slug' => 'arequipa', 'name_es' => 'Arequipa', 'is_active' => true, 'order' => 1]);
        $cusco = Region::create(['slug' => 'cusco', 'name_es' => 'Cusco', 'is_active' => true, 'order' => 2]);
        Tour::factory()->create(['region_id' => $cusco->id, 'is_published' => true]);

        $result = Region::active()->withPublishedTours()->get();

        $this->assertCount(1, $result);
        $this->assertSame('cusco', $result->first()->slug);
        $this->assertNotContains($arequipa->id, $result->pluck('id'));
    }

    /**
     * El caso explícito que probó este lote en local: Cusco NO estaba vacío
     * como asumía el brief — ya tenía tours reales — así que debe aparecer,
     * y Arequipa/Puno (sin ninguna fila de Region ni tour) deben seguir sin
     * aparecer, sin que nadie tenga que tocar código para ninguno de los dos
     * casos.
     */
    public function test_region_gets_a_real_published_tours_count(): void
    {
        $ica = Region::create(['slug' => 'ica', 'name_es' => 'Ica', 'is_active' => true, 'order' => 1]);
        Tour::factory()->create(['region_id' => $ica->id, 'is_published' => true]);
        Tour::factory()->create(['region_id' => $ica->id, 'is_published' => true]);
        Tour::factory()->create(['region_id' => $ica->id, 'is_published' => false]); // no cuenta

        $result = Region::active()->withPublishedTours()->get();

        $this->assertSame(2, $result->first()->published_tours_count);
    }

    public function test_inactive_region_is_excluded_even_with_published_tours(): void
    {
        $region = Region::create(['slug' => 'lima', 'name_es' => 'Lima', 'is_active' => false, 'order' => 1]);
        Tour::factory()->create(['region_id' => $region->id, 'is_published' => true]);

        $result = Region::active()->withPublishedTours()->get();

        $this->assertCount(0, $result);
    }

    public function test_category_without_any_published_tour_is_excluded(): void
    {
        $empty = Category::create(['slug' => 'otros-vacio', 'name_es' => 'Otros (vacío)', 'is_active' => true, 'order' => 1]);
        $real = Category::create(['slug' => 'aventura', 'name_es' => 'Tours de Aventura', 'is_active' => true, 'order' => 2]);
        Tour::factory()->create(['category_id' => $real->id, 'is_published' => true]);

        $result = Category::active()->withPublishedTours()->get();

        $this->assertCount(1, $result);
        $this->assertSame('aventura', $result->first()->slug);
        $this->assertNotContains($empty->id, $result->pluck('id'));
    }

    public function test_category_gets_a_real_published_tours_count(): void
    {
        $cat = Category::create(['slug' => 'cultural', 'name_es' => 'Tours Culturales', 'is_active' => true, 'order' => 1]);
        Tour::factory()->count(3)->create(['category_id' => $cat->id, 'is_published' => true]);
        Tour::factory()->create(['category_id' => $cat->id, 'is_published' => false]);

        $result = Category::active()->withPublishedTours()->get();

        $this->assertSame(3, $result->first()->published_tours_count);
    }

    public function test_region_image_url_accessor_is_null_without_a_hero_image(): void
    {
        $region = Region::create(['slug' => 'lima', 'name_es' => 'Lima', 'is_active' => true, 'order' => 1, 'hero_image' => null]);

        $this->assertNull($region->image_url);
    }
}
