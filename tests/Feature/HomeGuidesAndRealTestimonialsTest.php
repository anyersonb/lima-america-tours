<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Testimonial;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrato de HomeController hacia la vista (lote 2026-08, TAREAS 2 y 3):
 *
 *   $activeGuides     — colección de App\Models\Guide activos, ordenados.
 *   $realTestimonials — colección de App\Models\Testimonial (CMS puro, sin
 *                       mezclar con la API de Google/Tripadvisor) con `tour`
 *                       precargado, para "Nombre · fecha · tour".
 *
 * Ambas deben venir VACÍAS (nunca null) cuando no hay datos activos: el
 * maquetador oculta la sección con `->isEmpty()`, el mismo criterio para
 * los dos bloques.
 */
class HomeGuidesAndRealTestimonialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_exposes_an_empty_guides_collection_when_there_are_none(): void
    {
        $response = $this->get('/es');

        $response->assertOk();
        $response->assertViewHas('activeGuides', function ($guides) {
            return $guides instanceof \Illuminate\Support\Collection && $guides->isEmpty();
        });
    }

    public function test_home_exposes_only_active_guides_ordered(): void
    {
        Guide::factory()->create(['name' => 'QA_ Guía visible', 'is_active' => true, 'order' => 1]);
        Guide::factory()->create(['name' => 'QA_ Guía oculto', 'is_active' => false, 'order' => 0]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertViewHas('activeGuides', function ($guides) {
            return $guides->count() === 1 && $guides->first()->name === 'QA_ Guía visible';
        });
    }

    public function test_home_exposes_an_empty_real_testimonials_collection_when_there_are_none(): void
    {
        $response = $this->get('/es');

        $response->assertOk();
        $response->assertViewHas('realTestimonials', function ($testimonials) {
            return $testimonials instanceof \Illuminate\Support\Collection && $testimonials->isEmpty();
        });
    }

    public function test_home_exposes_active_testimonials_with_tour_eager_loaded(): void
    {
        $tour = Tour::factory()->create(['title_es' => 'QA_ Tour de prueba']);
        Testimonial::factory()->create([
            'name' => 'QA_ Cliente feliz',
            'is_active' => true,
            'tour_id' => $tour->id,
        ]);
        Testimonial::factory()->create([
            'name' => 'QA_ Reseña pendiente',
            'is_active' => false,
        ]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertViewHas('realTestimonials', function ($testimonials) use ($tour) {
            if ($testimonials->count() !== 1) {
                return false;
            }

            $only = $testimonials->first();

            return $only->name === 'QA_ Cliente feliz'
                && $only->relationLoaded('tour')
                && $only->tour->id === $tour->id
                && $only->displayDate() !== null;
        });
    }
}
