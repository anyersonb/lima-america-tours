<?php

namespace Tests\Feature;

use App\Filament\Resources\TourResource\Pages\CreateTour;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defecto #2 de docs/qa/F7-personas.md §g (F7, cro-validator):
 *
 * "El sistema no me deja escribir la coma pero yo no me doy cuenta, y
 * termino con "12050" en el campo — ¡mi tour hubiera quedado en $12,050 en
 * vez de $120.50 y nadie me hubiera avisado!"
 *
 * Reproduce el flujo completo del formulario de Filament (no solo la
 * función pura de normalización) para confirmar que "120,50" tecleado en
 * el campo Precio termina guardado como 120.50, y que un valor realmente
 * inválido (letras, doble separador) se rechaza con un mensaje claro en vez
 * de guardarse silenciosamente.
 */
class TourResourcePriceCommaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_comma_decimal_price_is_normalized_and_saved_correctly(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm([
                'title_es' => 'Tour de prueba QA F7',
                'price' => '120,50',
                'price_before' => '150,00',
                'currency' => 'USD',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::where('title_es', 'Tour de prueba QA F7')->firstOrFail();

        $this->assertSame(120.50, (float) $tour->price);
        $this->assertSame(150.00, (float) $tour->price_before);
    }

    public function test_malformed_price_is_rejected_with_a_clear_error_instead_of_being_guessed(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm([
                'title_es' => 'Tour precio inválido',
                'price' => '120,50,00',
                'currency' => 'USD',
            ])
            ->call('create')
            ->assertHasFormErrors(['price']);

        $this->assertDatabaseMissing('tours', ['title_es' => 'Tour precio inválido']);
    }
}
