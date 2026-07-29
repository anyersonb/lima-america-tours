<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El campo "Fecha" del buscador del hero viajaba a /buscar como referencia
 * decorativa: TourController::search() lo recibía y no lo usaba para nada, así
 * que el buscador ofrecía tours en fechas que el CMS ya tiene bloqueadas
 * (BlockedDate, las mismas que respeta la ficha de tour).
 *
 * Contrato: la fecha filtra de verdad.
 *   - Bloqueo global (tour_id null) en esa fecha  → ningún tour operativo + aviso.
 *   - Bloqueo de UN tour en esa fecha             → se cae solo ese tour.
 *   - Bloqueo por día de la semana                → se cae el tour ese weekday.
 *   - Fecha pasada o basura                       → se ignora, no revienta.
 *
 * Antes del fix: falla (el parámetro `fecha` no filtra nada).
 * Después del fix: pasa.
 */
class HeroSearchDateBlockedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recorta el HTML al área de resultados: el footer publica "Tours
     * Populares" con títulos de tours, así que buscarlos en la página completa
     * da falsos positivos.
     */
    private function resultsArea(string $html): string
    {
        $start = strpos($html, 'space-y-5');
        $this->assertNotFalse($start, 'No se encontró el contenedor de resultados.');

        $end = strpos($html, '<footer');

        return substr($html, $start, ($end !== false ? $end : strlen($html)) - $start);
    }

    public function test_globally_blocked_date_returns_no_tours_and_shows_a_notice(): void
    {
        $tour = Tour::factory()->create(['title_es' => 'City Tour Lima', 'is_published' => true]);
        $date = now()->addDays(10)->toDateString();

        BlockedDate::factory()->create(['date' => $date, 'tour_id' => null, 'weekday' => null]);

        $response = $this->get("/es/buscar?fecha={$date}");
        $response->assertOk();

        // Cero resultados operativos para esa fecha…
        $response->assertSee(__('ui.no_results_found', [], 'es'));
        // …con un aviso que explica POR QUÉ (no un vacío mudo)…
        $response->assertSee(__('ui.search_date_blocked', [], 'es'));
        // …y sin dejar la página muerta: se recomiendan tours reales.
        $this->assertStringContainsString($tour->slug, $this->resultsArea($response->getContent()));
    }

    public function test_tour_specific_blocked_date_only_drops_that_tour(): void
    {
        $blocked = Tour::factory()->create(['title_es' => 'Tour Bloqueado Paracas', 'is_published' => true]);
        Tour::factory()->create(['title_es' => 'Tour Libre Miraflores', 'is_published' => true]);
        $date = now()->addDays(12)->toDateString();

        BlockedDate::factory()->create(['date' => $date, 'tour_id' => $blocked->id, 'weekday' => null]);

        $response = $this->get("/es/buscar?fecha={$date}");
        $response->assertOk();

        $results = $this->resultsArea($response->getContent());

        $this->assertStringContainsString('Tour Libre Miraflores', $results);
        $this->assertStringNotContainsString('Tour Bloqueado Paracas', $results);

        // Con resultados disponibles no corresponde el aviso de fecha bloqueada.
        $response->assertDontSee(__('ui.search_date_blocked', [], 'es'));
    }

    public function test_blocked_weekday_drops_the_tour_on_that_weekday(): void
    {
        $tour = Tour::factory()->create(['title_es' => 'Tour Solo Fines de Semana', 'is_published' => true]);
        Tour::factory()->create(['title_es' => 'Tour Todos los Dias', 'is_published' => true]);

        // Próximo miércoles (weekday 3 con la convención de BlockedDate: 0=domingo).
        $date = now()->next(\Carbon\Carbon::WEDNESDAY);

        BlockedDate::factory()->create([
            'date' => null,
            'weekday' => 3,
            'tour_id' => $tour->id,
        ]);

        $response = $this->get('/es/buscar?fecha='.$date->toDateString());
        $response->assertOk();

        $results = $this->resultsArea($response->getContent());

        $this->assertStringContainsString('Tour Todos los Dias', $results);
        $this->assertStringNotContainsString('Tour Solo Fines de Semana', $results);
    }

    public function test_past_and_malformed_dates_are_ignored_without_breaking(): void
    {
        Tour::factory()->create(['title_es' => 'City Tour Lima', 'is_published' => true]);

        foreach ([now()->subDays(5)->toDateString(), 'no-es-fecha', '2026-99-99', '<script>x</script>'] as $bad) {
            $response = $this->get('/es/buscar?fecha='.urlencode($bad));

            $response->assertOk();
            $this->assertStringContainsString(
                'City Tour Lima',
                $this->resultsArea($response->getContent()),
                "La fecha inválida \"{$bad}\" no debería filtrar nada."
            );
        }
    }
}
