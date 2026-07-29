<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Personas" era el cuarto campo decorativo del buscador: llegaba como query
 * param y TourController::search() no lo miraba. Los tours ya tienen
 * `max_capacity` en la BD (columna real, editable en el panel), así que el
 * campo puede filtrar de verdad: si alguien busca para 6 personas, no tiene
 * sentido ofrecerle un tour privado con cupo máximo 2.
 *
 * Contrato: `max_capacity` nulo = sin límite declarado (siempre se muestra).
 *
 * Antes del fix: falla (el parámetro `pax` no filtra nada).
 * Después del fix: pasa.
 */
class HeroSearchPaxCapacityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recorta el HTML al área de RESULTADOS.
     *
     * Importante: el footer publica una lista "Tours Populares" con títulos de
     * tours, así que buscar un título en la página completa da falsos positivos
     * (un tour filtrado sigue apareciendo abajo, en el footer). Las aserciones
     * tienen que mirar solo la zona de resultados.
     */
    private function resultsArea(string $html): string
    {
        $start = strpos($html, 'space-y-5');
        $this->assertNotFalse($start, 'No se encontró el contenedor de resultados.');

        $end = strpos($html, '<footer');

        return substr($html, $start, ($end !== false ? $end : strlen($html)) - $start);
    }

    private function results(string $url): string
    {
        $response = $this->get($url);
        $response->assertOk();

        return $this->resultsArea($response->getContent());
    }

    public function test_pax_filters_out_tours_with_smaller_capacity(): void
    {
        Tour::factory()->create(['title_es' => 'Tour Privado Dos Personas', 'max_capacity' => 2, 'is_published' => true]);
        Tour::factory()->create(['title_es' => 'Tour Grupal Doce', 'max_capacity' => 12, 'is_published' => true]);
        Tour::factory()->create(['title_es' => 'Tour Sin Limite Declarado', 'max_capacity' => null, 'is_published' => true]);

        $results = $this->results('/es/buscar?pax=6');

        $this->assertStringContainsString('Tour Grupal Doce', $results);
        $this->assertStringContainsString('Tour Sin Limite Declarado', $results);
        $this->assertStringNotContainsString(
            'Tour Privado Dos Personas',
            $results,
            'Un tour con cupo máximo 2 no debe ofrecerse a quien busca para 6 personas.'
        );
    }

    public function test_capacity_exactly_equal_to_pax_still_qualifies(): void
    {
        Tour::factory()->create(['title_es' => 'Tour Cupo Exacto Cuatro', 'max_capacity' => 4, 'is_published' => true]);

        $this->assertStringContainsString('Tour Cupo Exacto Cuatro', $this->results('/es/buscar?pax=4'));
    }

    public function test_ten_plus_option_is_understood_as_ten(): void
    {
        Tour::factory()->create(['title_es' => 'Tour Grupo Grande Veinte', 'max_capacity' => 20, 'is_published' => true]);
        Tour::factory()->create(['title_es' => 'Tour Cupo Ocho', 'max_capacity' => 8, 'is_published' => true]);

        $results = $this->results('/es/buscar?pax='.urlencode('10+'));

        $this->assertStringContainsString('Tour Grupo Grande Veinte', $results);
        $this->assertStringNotContainsString('Tour Cupo Ocho', $results, 'El valor "10+" debe entenderse como 10 personas.');
    }

    public function test_absent_or_malformed_pax_does_not_filter(): void
    {
        Tour::factory()->create(['title_es' => 'Tour Privado Dos Personas', 'max_capacity' => 2, 'is_published' => true]);

        foreach (['', 'muchas', '0', '-3'] as $bad) {
            $this->assertStringContainsString(
                'Tour Privado Dos Personas',
                $this->results('/es/buscar?pax='.urlencode($bad)),
                "El valor \"{$bad}\" no debería filtrar nada."
            );
        }
    }
}
