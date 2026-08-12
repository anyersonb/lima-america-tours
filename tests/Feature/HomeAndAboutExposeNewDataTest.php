<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Guide;
use App\Models\Region;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que el home y /nosotros reciban las variables nuevas que el
 * maquetador va a consumir (guías, destinos reales, categorías reales). No
 * comprueba el HTML de las secciones (todavía no maquetadas): comprueba el
 * CONTRATO de datos, que es lo que corresponde a este lote.
 */
class HomeAndAboutExposeNewDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_exposes_destination_regions_and_categories_with_real_counts(): void
    {
        $region = Region::create(['slug' => 'ica', 'name_es' => 'Ica', 'is_active' => true, 'order' => 1]);
        $category = Category::create(['slug' => 'aventura', 'name_es' => 'Tours de Aventura', 'is_active' => true, 'order' => 1]);
        Tour::factory()->create(['region_id' => $region->id, 'category_id' => $category->id, 'is_published' => true]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertViewHas('destinationRegions', function ($regions) {
            return $regions->count() === 1 && $regions->first()->slug === 'ica';
        });
        $response->assertViewHas('homeCategories', function ($categories) {
            return $categories->count() === 1 && $categories->first()->slug === 'aventura';
        });
    }

    public function test_home_hides_regions_and_categories_without_published_tours(): void
    {
        Region::create(['slug' => 'sin-tours', 'name_es' => 'Sin tours', 'is_active' => true, 'order' => 1]);
        Category::create(['slug' => 'sin-tours', 'name_es' => 'Sin tours', 'is_active' => true, 'order' => 1]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertViewHas('destinationRegions', fn ($regions) => $regions->isEmpty());
        $response->assertViewHas('homeCategories', fn ($categories) => $categories->isEmpty());
    }

    public function test_about_page_exposes_active_guides_ordered(): void
    {
        Guide::factory()->create(['name' => 'QA_ Segundo', 'order' => 2, 'is_active' => true]);
        Guide::factory()->create(['name' => 'QA_ Primero', 'order' => 1, 'is_active' => true]);
        Guide::factory()->create(['name' => 'QA_ Inactivo', 'order' => 0, 'is_active' => false]);

        $response = $this->get('/es/nosotros');

        $response->assertOk();
        $response->assertViewHas('guides', function ($guides) {
            return $guides->count() === 2 && $guides->first()->name === 'QA_ Primero';
        });
    }

    public function test_about_page_exposes_empty_guides_collection_when_none_active(): void
    {
        $response = $this->get('/es/nosotros');

        $response->assertOk();
        $response->assertViewHas('guides', fn ($guides) => $guides->isEmpty());
    }

    public function test_about_page_exposes_destination_regions_with_published_tours_only(): void
    {
        $withTours = Region::create(['slug' => 'lima', 'name_es' => 'Lima', 'is_active' => true, 'order' => 1]);
        Region::create(['slug' => 'arequipa', 'name_es' => 'Arequipa', 'is_active' => true, 'order' => 2]);
        Tour::factory()->create(['region_id' => $withTours->id, 'is_published' => true]);

        $response = $this->get('/es/nosotros');

        $response->assertOk();
        $response->assertViewHas('destinationRegions', function ($regions) {
            return $regions->count() === 1 && $regions->first()->slug === 'lima';
        });
    }
}
