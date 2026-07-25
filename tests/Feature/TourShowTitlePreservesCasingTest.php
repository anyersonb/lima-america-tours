<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo #7 de docs/qa/panel-filament.md (F2, cro-validator):
 *
 * El `<title>` de la ficha de tour aplicaba una recapitalización
 * (mb_convert_case a MB_CASE_TITLE sobre el título en minúsculas) que
 * rompía mayúsculas intencionales: "QA_ Tour Prueba ñÑ áéíóú" se
 * convertía en "Qa_ Tour Prueba Ññ Áéíóú" en la pestaña del navegador.
 *
 * Este test crea un tour con un título de mayúsculas "hostiles" (siglas +
 * tildes) y confirma que el <title> de resources/views/tours/show.blade.php
 * conserva el texto exactamente como lo escribió el editor.
 */
class TourShowTitlePreservesCasingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_title_tag_preserves_original_casing(): void
    {
        $tour = Tour::factory()->create([
            'title_es' => 'QA_ Tour Prueba ñÑ áéíóú',
            'is_published' => true,
        ]);

        $response = $this->get('/es/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('<title>QA_ Tour Prueba ñÑ áéíóú', false);
        $response->assertDontSee('Qa_ Tour Prueba');
    }
}
