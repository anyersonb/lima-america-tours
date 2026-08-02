<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAREA 3 del lote 2026-08: para mostrar "Nombre · fecha · tour" en la
 * portada, `Testimonial` necesitaba una fecha propia y confiable (no el
 * `review_date` de texto libre importado de WP, que trae formatos
 * irregulares). `reviewed_at` (migración 2026_08_02_000002) cubre eso.
 */
class TestimonialReviewedAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewed_at_defaults_to_today_when_not_set(): void
    {
        $testimonial = Testimonial::create([
            'name' => 'QA_ Sin fecha',
            'quote_es' => 'Excelente servicio.',
            'rating' => 5,
        ]);

        $this->assertNotNull($testimonial->reviewed_at);
        $this->assertTrue($testimonial->reviewed_at->isToday());
    }

    public function test_reviewed_at_is_respected_when_explicitly_set(): void
    {
        $testimonial = Testimonial::create([
            'name' => 'QA_ Con fecha',
            'quote_es' => 'Excelente servicio.',
            'rating' => 5,
            'reviewed_at' => '2024-03-10',
        ]);

        $this->assertSame('2024-03-10', $testimonial->reviewed_at->toDateString());
    }

    public function test_display_date_prefers_reviewed_at_over_legacy_review_date(): void
    {
        $testimonial = Testimonial::create([
            'name' => 'QA_ Legacy',
            'quote_es' => 'Excelente servicio.',
            'rating' => 5,
            'reviewed_at' => '2024-03-10',
            'review_date' => '03 Mar 2024',
        ]);

        $this->assertSame('2024-03-10', $testimonial->displayDate()->toDateString());
    }

    public function test_display_date_falls_back_to_legacy_review_date_string(): void
    {
        $testimonial = new Testimonial([
            'name' => 'QA_ Legacy sin reviewed_at',
            'quote_es' => 'Excelente servicio.',
            'rating' => 5,
            'review_date' => '10 March 2024',
        ]);
        // Simular un registro legacy: sin pasar por el hook `creating`.
        $testimonial->reviewed_at = null;

        $this->assertSame('2024-03-10', $testimonial->displayDate()->toDateString());
    }

    public function test_display_date_never_throws_on_an_unparseable_legacy_string(): void
    {
        $testimonial = new Testimonial([
            'name' => 'QA_ Fecha rara',
            'quote_es' => 'Excelente servicio.',
            'rating' => 5,
            'review_date' => 'no es una fecha',
        ]);
        $testimonial->reviewed_at = null;
        $testimonial->created_at = now();

        $this->assertNotNull($testimonial->displayDate());
    }
}
