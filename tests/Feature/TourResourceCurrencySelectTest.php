<?php

namespace Tests\Feature;

use App\Filament\Resources\TourResource\Pages\CreateTour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CRO #2: el campo Moneda de TourResource era un TextInput libre
 * (->maxLength(3), sin opciones fijas) — un editor podía cargar cualquier
 * texto de 3 caracteres ("ars", "sol", etc.) y el panel lo aceptaba sin
 * avisar, aunque el negocio cobra 100% en soles (PEN) vía Culqi. Ahora es un
 * Select con únicamente PEN (default) y USD.
 */
class TourResourceCurrencySelectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@limaamericatours.com']);
    }

    private function baseForm(array $overrides = []): array
    {
        return array_merge([
            'title_es' => 'Tour de prueba moneda',
            'price' => '100',
            'currency' => 'PEN',
        ], $overrides);
    }

    public function test_currency_field_only_accepts_pen_or_usd(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm($this->baseForm(['currency' => 'ARS']))
            ->call('create')
            ->assertHasFormErrors(['currency']);

        $this->assertDatabaseMissing('tours', ['title_es' => 'Tour de prueba moneda']);
    }

    public function test_currency_field_accepts_pen(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm($this->baseForm(['currency' => 'PEN']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tours', ['title_es' => 'Tour de prueba moneda', 'currency' => 'PEN']);
    }

    public function test_currency_field_accepts_usd(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm($this->baseForm(['currency' => 'USD']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tours', ['title_es' => 'Tour de prueba moneda', 'currency' => 'USD']);
    }

    public function test_currency_field_defaults_to_pen_in_the_create_form(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.tours.create'));

        $response->assertOk();
        // Filament renders the selected option's label somewhere in the
        // Alpine/Choices markup for a Select with a default value.
        $response->assertSee('PEN (Soles)');
    }
}
