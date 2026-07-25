<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Testimonial;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hallazgos 1-4 y (evidencia de) 5 de la corrección visual de la ficha de
 * tour encargada por el CRO (ver docs/qa/ficha-tour.md, filas #1/#2/#3/#5/#9
 * de la tabla F2). Corre contra el entorno "testing" (sqlite en memoria),
 * nunca contra lima_america/lima_america_qa.
 */
class TourShowFichaFixesTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    // ─────────────────────────────────────────────────────────────
    //  Hallazgo #1 — fecha bloqueada: mensaje visible + datos para el JS
    // ─────────────────────────────────────────────────────────────

    public function test_tour_show_displays_error_after_booking_a_blocked_date(): void
    {
        $tour = Tour::factory()->published()->create(['price' => 100]);
        $blockedDate = now()->addDays(15)->toDateString();

        // Inserción directa (bypass del cast 'date' de Eloquent): bajo SQLite
        // (entorno de test) Eloquent serializa los atributos date/datetime con
        // el formato de la conexión ("Y-m-d H:i:s") sin importar el tipo de
        // columna, así que BlockedDate::factory()->create(['date' => ...])
        // guardaría "2026-08-09 00:00:00" y el ->where('date', $date) plano
        // de BlockedDate::isBlocked() (no se toca ese modelo, es del backend)
        // nunca haría match contra el string "2026-08-09". En MySQL real
        // (producción/QA) la columna es DATE de verdad y sí matchea — ya
        // confirmado en vivo por cro-validator (docs/qa/ficha-tour.md, fila
        // #1). Se inserta el valor ya truncado a fecha para reproducir aquí
        // el mismo comportamiento que tiene MySQL.
        DB::table('blocked_dates')->insert([
            'tour_id' => $tour->id,
            'date' => $blockedDate,
            'weekday' => null,
            'reason' => 'QA_ test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tourUrl = '/'.self::LOCALE.'/tours/detalle/'.$tour->slug;

        $response = $this->from($tourUrl)->post(
            route('cart.store', ['locale' => self::LOCALE]),
            [
                'tour_id' => $tour->id,
                'adults' => 2,
                'children' => 0,
                'travel_date' => $blockedDate,
            ]
        );

        $response->assertRedirect($tourUrl);
        $response->assertSessionHasErrors('travel_date');

        // Antes del fix, tours/show.blade.php nunca leía $errors: el
        // visitante no veía ningún mensaje tras el rechazo del backend.
        $follow = $this->get($tourUrl);
        $follow->assertOk();
        $follow->assertSee(__('booking.date_blocked'));
    }

    public function test_tour_show_exposes_blocked_dates_and_weekdays_for_the_datepicker(): void
    {
        $tour = Tour::factory()->published()->create();
        $blockedDate = now()->addDays(20)->toDateString();

        BlockedDate::factory()->create([
            'tour_id' => $tour->id,
            'date' => $blockedDate,
            'weekday' => null,
        ]);
        BlockedDate::factory()->create([
            'tour_id' => $tour->id,
            'date' => null,
            'weekday' => 1,
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        // Las listas que el JS usa para invalidar el <input type="date">
        // (hallazgo #1b) deben llegar embebidas en la página.
        $response->assertSee($blockedDate);
        $response->assertSee('id="bkDateError"', false);
        $response->assertSee('id="bkDateField"', false);
    }

    // ─────────────────────────────────────────────────────────────
    //  Hallazgo #2 — comparativa convencional vs. premium
    // ─────────────────────────────────────────────────────────────

    public function test_tour_show_renders_comparison_block_when_active_with_data(): void
    {
        $tour = Tour::factory()->published()->create([
            'comparison' => [
                'enabled' => true,
                'color' => 'teal',
                'title_es' => 'QA_ Tour convencional',
                'title_hl_es' => 'QA_ premium',
                'conv_es' => ['QA_ Grupo grande', 'QA_ Horario fijo'],
                'prem_es' => ['QA_ Grupo reducido', 'QA_ Horario flexible'],
                'footer_es' => 'QA_ Frase final del comparativo',
            ],
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('QA_ Grupo grande');
        $response->assertSee('QA_ Grupo reducido');
        $response->assertSee('QA_ Frase final del comparativo');
    }

    public function test_tour_show_hides_comparison_block_when_disabled(): void
    {
        $tour = Tour::factory()->published()->create([
            'comparison' => [
                'enabled' => false,
                'conv_es' => ['QA_ No debería verse'],
                'prem_es' => ['QA_ Tampoco esto'],
            ],
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertDontSee('QA_ No debería verse');
    }

    // ─────────────────────────────────────────────────────────────
    //  Hallazgo #3 — reseñas aprobadas del tour
    // ─────────────────────────────────────────────────────────────

    public function test_tour_show_renders_approved_testimonials(): void
    {
        $tour = Tour::factory()->published()->create();

        Testimonial::factory()->create([
            'tour_id' => $tour->id,
            'name' => 'QA_ Cliente Prueba',
            'quote_es' => 'QA_ Excelente tour, muy recomendable.',
            'rating' => 5,
            'is_active' => true,
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('QA_ Cliente Prueba');
        $response->assertSee('QA_ Excelente tour, muy recomendable.');
    }

    public function test_tour_show_hides_unapproved_testimonials(): void
    {
        $tour = Tour::factory()->published()->create();

        Testimonial::factory()->create([
            'tour_id' => $tour->id,
            'name' => 'QA_ Cliente Sin Aprobar',
            'quote_es' => 'QA_ Reseña pendiente de moderación.',
            'is_active' => false,
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertDontSee('QA_ Cliente Sin Aprobar');
    }

    // ─────────────────────────────────────────────────────────────
    //  Hallazgo #4 — badge_text/badge_type en la ficha
    // ─────────────────────────────────────────────────────────────

    public function test_tour_show_renders_badge_when_tour_has_one(): void
    {
        $tour = Tour::factory()->published()->create([
            'badge_text' => 'QA_ CUPOS LIMITADOS',
            'badge_type' => 'success',
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('QA_ CUPOS LIMITADOS');
        $response->assertSee('lat-detail-badge--g', false);
    }

    public function test_tour_show_omits_badge_when_tour_has_none(): void
    {
        $tour = Tour::factory()->published()->create(['badge_text' => null]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertDontSee('lat-detail-badge', false);
    }

    // ─────────────────────────────────────────────────────────────
    //  Hallazgo #9 (breadcrumb) — evidencia de markup truncable
    // ─────────────────────────────────────────────────────────────

    public function test_tour_show_breadcrumb_has_truncation_markup_for_long_titles(): void
    {
        $longTitle = 'QA_ '.str_repeat('Título Extremadamente Largo Para Probar El Truncado ', 4);

        $tour = Tour::factory()->published()->create(['title_es' => $longTitle]);

        $response = $this->get('/'.self::LOCALE.'/tours/detalle/'.$tour->slug);

        $response->assertOk();
        $response->assertSee('lat-crumb__current', false);
        $response->assertSee('title="'.e($longTitle).'"', false);
    }
}
