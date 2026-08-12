<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hallazgo CRO 2026-08-11 (lote mockups ago-2026), FIX 1 — BLOQUEANTE:
 * `home_hero_cta_primary_url` traía "https://limaamericatours.com/tours"
 * hardcodeado, sacando al visitante de staging/local hacia PRODUCCIÓN cada
 * vez que tocaba el botón principal del hero. El dato ya se limpió a mano
 * en la BD; estas pruebas cubren que no pueda volver a pasar por
 * descuido, bloqueando el guardado en el panel (App\Filament\Pages\Settings)
 * en vez de solo confiar en que nadie vuelva a pegar esa URL.
 *
 * El guard vive en el formulario (Settings::selfOrLocalHosts() + ->rule()),
 * NO en el blade del hero: home.blade.php sigue leyendo el Setting tal cual
 * (fuera de alcance de este fix — lo edita el maquetador en paralelo). Por
 * eso la prueba de "no se puede volver a guardar" se hace contra el panel,
 * y la de "el fallback es seguro" contra el render con el Setting vacío.
 */
class HeroCtaPrimaryUrlGuardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_settings_panel_rejects_the_hero_cta_url_when_it_points_to_the_production_domain(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_hero_cta_primary_url' => 'https://limaamericatours.com/tours'])
            ->call('save')
            ->assertHasFormErrors(['home_hero_cta_primary_url']);

        // Nunca debe llegar a guardarse: ni siquiera queda un valor parcial.
        $this->assertNull(Setting::get('home_hero_cta_primary_url'));
    }

    public function test_settings_panel_rejects_the_hero_cta_url_when_it_points_to_www_production_domain(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_hero_cta_primary_url' => 'https://www.limaamericatours.com/tours?utm=x'])
            ->call('save')
            ->assertHasFormErrors(['home_hero_cta_primary_url']);
    }

    public function test_settings_panel_rejects_the_hero_cta_url_when_it_points_to_localhost(): void
    {
        // Caso simétrico: una URL de pruebas local pegada por descuido no
        // debe poder colarse tampoco (rompería igual si esa BD llega a prod).
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_hero_cta_primary_url' => 'http://127.0.0.1:8001/tours'])
            ->call('save')
            ->assertHasFormErrors(['home_hero_cta_primary_url']);
    }

    public function test_settings_panel_accepts_a_relative_path_for_the_hero_cta_url(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_hero_cta_primary_url' => '/tours'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('/tours', Setting::get('home_hero_cta_primary_url'));
    }

    public function test_settings_panel_accepts_a_genuinely_external_url_for_the_hero_cta_url(): void
    {
        // Una URL absoluta a una web externa de verdad SIGUE siendo válida:
        // el guard es por host propio, no por "cualquier URL absoluta".
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_hero_cta_primary_url' => 'https://wa.me/51999999999'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://wa.me/51999999999', Setting::get('home_hero_cta_primary_url'));
    }

    public function test_settings_panel_accepts_an_empty_hero_cta_url(): void
    {
        // Vacío es el estado correcto por defecto (blade cae a route('tours.index')).
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_hero_cta_primary_url' => ''])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_home_hero_cta_falls_back_to_the_internal_tours_route_when_the_setting_is_empty(): void
    {
        // Con el Setting vacío (estado actual en BD tras el fix), el CTA del
        // hero jamás debe apuntar al dominio de producción ni a otro host
        // absoluto: debe resolver siempre a la ruta interna de tours.
        $response = $this->get('/es')->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('https://limaamericatours.com/tours', $content);
        $this->assertStringContainsString(route('tours.index', ['locale' => 'es']), $content);
    }
}
