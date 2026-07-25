<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo #4 de docs/qa/ficha-tour.md (F2, cro-validator):
 *
 * La ficha de tour generaba <title>/meta description/OG genéricos y nunca
 * usaba seo_title/seo_description/seo_image/seo_keywords del Tour, aunque
 * el CMS ya los guardaba (Tabs::make('SEO') en TourResource). Cada ficha
 * terminaba compartiendo los metadatos sociales genéricos del sitio.
 *
 * Fix: TourController@show resuelve estos valores (con fallback al
 * título/descripción/portada del propio tour) y los pasa a la vista;
 * resources/views/layouts/app.blade.php los prioriza sobre el
 *
 * @section('title'/'description') que tours/show.blade.php ya define
 * (no se tocó ese archivo — ver docs/qa/FIXES.md).
 */
class TourShowSeoMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_show_uses_seo_fields_when_the_tour_has_them(): void
    {
        $tour = Tour::factory()->create([
            'is_published' => true,
            'seo_title' => 'QA_ SEO Title Machu Picchu Aventura',
            'seo_description' => 'QA_ SEO meta description personalizada para este tour.',
            'seo_keywords' => ['qa_keyword_prueba', 'machu picchu'],
        ]);

        $response = $this->get('/es/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('<title>QA_ SEO Title Machu Picchu Aventura</title>', false);
        $response->assertSee('<meta name="description" content="QA_ SEO meta description personalizada para este tour.">', false);
        $response->assertSee('<meta property="og:title" content="QA_ SEO Title Machu Picchu Aventura">', false);
        $response->assertSee('<meta property="og:description" content="QA_ SEO meta description personalizada para este tour.">', false);
        $response->assertSee('<meta name="keywords" content="qa_keyword_prueba, machu picchu">', false);
    }

    public function test_tour_show_falls_back_to_tour_title_when_seo_fields_are_empty(): void
    {
        $tour = Tour::factory()->create([
            'is_published' => true,
            'title_es' => 'QA_ Tour Sin SEO Configurado',
            'seo_title' => null,
            'seo_description' => null,
        ]);

        $response = $this->get('/es/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('<title>QA_ Tour Sin SEO Configurado — '.__('seo.site_name').'</title>', false);
    }
}
