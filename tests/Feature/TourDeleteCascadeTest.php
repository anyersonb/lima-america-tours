<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Testimonial;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo #7 de docs/qa/ficha-tour.md (F2, cro-validator):
 *
 * Al borrar un Tour desde el admin (soft delete estándar de Filament),
 * Testimonial y BlockedDate ligados por tour_id quedaban huérfanos: la fila
 * seguía en la BD apuntando a un tour_id que Tour::find() ya no resuelve
 * (excluido por el scope global de SoftDeletes), sin cascadeOnDelete ni
 * limpieza en el modelo. Se confirmó manualmente vía tinker en QA y se
 * purgó a mano; este test cubre la regresión.
 *
 * Tour::booted() ahora escucha el evento `deleting` y borra en cascada sus
 * testimonials()/blockedDates() (ninguno de los dos tiene soft deletes
 * propios), tanto en un delete() normal (soft) como en un forceDelete().
 */
class TourDeleteCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_a_tour_removes_its_testimonials_and_blocked_dates(): void
    {
        $tour = Tour::factory()->create();
        $testimonial = Testimonial::factory()->create(['tour_id' => $tour->id]);
        $blockedDate = BlockedDate::factory()->create(['tour_id' => $tour->id]);

        // Otro tour + sus propios hijos NO deben verse afectados.
        $otherTour = Tour::factory()->create();
        $otherTestimonial = Testimonial::factory()->create(['tour_id' => $otherTour->id]);
        $otherBlockedDate = BlockedDate::factory()->create(['tour_id' => $otherTour->id]);

        $tour->delete(); // soft delete (Tour usa SoftDeletes)

        $this->assertSoftDeleted('tours', ['id' => $tour->id]);
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
        $this->assertDatabaseMissing('blocked_dates', ['id' => $blockedDate->id]);

        // Los hijos del tour que NO se borró siguen intactos.
        $this->assertDatabaseHas('testimonials', ['id' => $otherTestimonial->id]);
        $this->assertDatabaseHas('blocked_dates', ['id' => $otherBlockedDate->id]);
    }

    public function test_force_deleting_a_tour_also_removes_its_testimonials_and_blocked_dates(): void
    {
        $tour = Tour::factory()->create();
        $testimonial = Testimonial::factory()->create(['tour_id' => $tour->id]);
        $blockedDate = BlockedDate::factory()->create(['tour_id' => $tour->id]);

        $tour->forceDelete();

        $this->assertDatabaseMissing('tours', ['id' => $tour->id]);
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
        $this->assertDatabaseMissing('blocked_dates', ['id' => $blockedDate->id]);
    }
}
