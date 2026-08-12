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
 * Causa raíz original: el footer leía __('footer.address') (string fijo de
 * idioma) en vez del Setting 'contact_address_{locale}'.
 *
 * Vuelta de tuerca 2026-08-11: el fallback a __('footer.address') SEGUÍA
 * ahí para el caso "Setting vacío" — y ese string fijo resultó ser una
 * SEGUNDA dirección sin confirmar (Jr. Lampa 209), tan sospechosa como la
 * que trae el Setting por defecto (Av. Larcomar 233, idéntica a la de Lima
 * View Tours, OTRO cliente). Se retiró el fallback de idioma por completo:
 * sin dirección real cargada, el bloque se OCULTA — dos direcciones
 * dudosas no se resuelven "eligiendo la que suena mejor", y esta clase de
 * test es justo la que antes documentaba el bug como si fuera correcto
 * (afirmaba que el fallback DEBÍA aparecer).
 */
class FooterAddressSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_footer_shows_the_address_configured_in_settings(): void
    {
        Setting::set('contact_address_es', 'Av. Real Confirmada 123, Lima');

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('Av. Real Confirmada 123, Lima');
        $response->assertDontSee('Jr. Lampa 209, Lima Center');
        $response->assertDontSee('Av. Larcomar 233');
    }

    public function test_home_footer_hides_the_address_block_when_none_is_configured(): void
    {
        Setting::set('contact_address_es', '');
        Setting::set('contact_address_en', '');

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        // Ni el texto heredado del fork ni la dirección de Lima View Tours:
        // ninguna de las dos está confirmada, así que ninguna debe imprimirse.
        $response->assertDontSee('Jr. Lampa 209, Lima Center');
        $response->assertDontSee('Av. Larcomar 233');
    }
}
