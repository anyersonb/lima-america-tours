<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El panel tiene que hablar en la MISMA moneda que el sitio cobra.
 *
 * Sustituye a TourResourceMoneyPenTest, que fijaba "S/" a mano. Motivo del
 * test original: varias columnas y campos del panel mostraban "$"/"USD"
 * mientras el sitio cobraba en soles, y la clienta CARGA los precios desde el
 * panel — si el campo dice "$" carga pensando en dólares y el monto termina
 * mal interpretado. Ese riesgo no desapareció al cambiar a USD el 2026-07-29:
 * simplemente se invirtió (ahora el símbolo equivocado es "S/"). Por eso se
 * compara contra Money::prefix(Money::site()) en vez de contra un literal.
 *
 * Cubre el listado de Tours (columna Precio) y el formulario de creación
 * (prefijo del campo Precio actual).
 */
class TourResourceMoneySymbolTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@limaamericatours.com']);
    }

    /**
     * Prefijo COMPLETO de la moneda que NO es la del sitio ("S/ " / "$").
     *
     * Con el prefijo completo y no con "S/" suelto: ese par de caracteres
     * aparece dentro de texto legítimo (p.ej. "iOS/Android") y buscarlo suelto
     * produce rojos que no son defectos de moneda.
     */
    private function wrongPrefix(): string
    {
        return Money::prefix(Money::site() === 'PEN' ? 'USD' : 'PEN');
    }

    public function test_tour_index_shows_the_site_currency_symbol(): void
    {
        Tour::factory()->create([
            'title_es' => 'Tour QA moneda',
            'price' => 120.50,
            'currency' => Money::site(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.tours.index'));

        $response->assertOk();
        $response->assertSee(Money::prefix(Money::site()), false);
        $response->assertDontSee($this->wrongPrefix(), false);
    }

    public function test_tour_create_form_price_field_is_prefixed_with_the_site_currency(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.tours.create'));

        $response->assertOk();
        $response->assertSee(Money::prefix(Money::site()), false);
        $response->assertDontSee($this->wrongPrefix(), false);
    }
}
