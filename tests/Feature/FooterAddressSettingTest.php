<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Defecto #3 / #5 de docs/qa/F7-personas.md §g (F7, cro-validator):
 *
 * "Cambio el teléfono y la dirección... Cuando voy a la web, el teléfono sí
 * cambió... Pero la dirección sigue diciendo 'Jr. Lampa 209, Lima Center',
 * que ni siquiera es la que puse antes ni la nueva."
 *
 * Causa raíz: el footer leía __('footer.address') (string fijo de idioma)
 * en vez del Setting 'contact_address_{locale}' que sí se guarda
 * correctamente desde Configuración → Contacto. Este test confirma que,
 * con el Setting seteado, el home lo muestra; y que sin Setting, cae al
 * texto por defecto (sin romper instalaciones que aún no lo configuraron).
 */
class FooterAddressSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_footer_shows_the_address_configured_in_settings(): void
    {
        Setting::set('contact_address_es', 'Av. Larcomar 233, Of. 410 — Miraflores, Lima');

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('Av. Larcomar 233, Of. 410 — Miraflores, Lima');
        $response->assertDontSee('Jr. Lampa 209, Lima Center');
    }

    public function test_home_footer_falls_back_to_default_text_when_no_address_is_configured(): void
    {
        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('Jr. Lampa 209, Lima Center');
    }
}
