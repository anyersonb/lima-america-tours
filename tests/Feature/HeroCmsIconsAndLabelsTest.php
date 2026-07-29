<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Del hero quedaban dos cosas fuera del CMS, y el brief las pide dentro:
 *   1) El ÍCONO de cada uno de los 4 features (el texto ya era editable, el
 *      ícono era un SVG hardcodeado en el Blade).
 *   2) El TEXTO del pill de WhatsApp ("Escríbenos por WhatsApp").
 *
 * La clienta tiene que poder cambiarlos sola desde Configuración → Home, en
 * los tres idiomas, sin tocar código.
 *
 * Antes del fix: falla (los campos no existen en el formulario de Settings ni
 * se leen en el home).
 * Después del fix: pasa.
 */
class HeroCmsIconsAndLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_icons_can_be_switched_from_settings(): void
    {
        // Por defecto, el slot 2 es el escudo del mockup.
        $html = $this->get('/es')->assertOk()->getContent();
        $this->assertStringContainsString('data-hero-icon="shield"', $html);

        Setting::set('home_hero_trust_2_icon', 'star');

        $html = $this->get('/es')->assertOk()->getContent();
        $this->assertStringContainsString('data-hero-icon="star"', $html, 'El ícono elegido en el panel no llegó al hero.');
    }

    public function test_unknown_icon_key_falls_back_instead_of_breaking_the_home(): void
    {
        Setting::set('home_hero_trust_1_icon', 'ícono-que-no-existe');

        $response = $this->get('/es');

        $response->assertOk();
        $this->assertStringContainsString('data-hero-icon="', $response->getContent());
    }

    /**
     * El pill de WhatsApp del hero se retiró el 2026-07-29: había dos CTAs del
     * mismo canal en la primera pantalla (el pill y el FAB flotante). Este test
     * blinda que no vuelva por accidente y que el FAB siga siendo el único.
     */
    public function test_hero_has_no_whatsapp_pill_and_the_floating_button_remains(): void
    {
        Setting::set('whatsapp', '51957299438');

        $html = $this->get('/es')->assertOk()->getContent();

        $heroStart = strpos($html, 'lat-hero__media');
        $heroEnd = strpos($html, 'lat-hero__search-wrap');
        $hero = substr($html, (int) $heroStart, (int) $heroEnd - (int) $heroStart);

        $this->assertStringNotContainsString('lat-btn--wa', $hero, 'Volvió el pill de WhatsApp al hero.');
        $this->assertStringNotContainsString('wa.me', $hero, 'El hero no debe llevar enlaces a WhatsApp.');

        // Pero el FAB global sigue ahí (es el único CTA de WhatsApp del sitio).
        $this->assertStringContainsString('id="waFab"', $html);
        $this->assertStringContainsString('wa.me/51957299438', $html);
    }

    public function test_admin_can_save_icons_and_whatsapp_label_from_the_settings_page(): void
    {
        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'home_hero_trust_1_icon' => 'map',
                'home_hero_trust_2_icon' => 'clock',
                'home_hero_trust_3_icon' => 'headset',
                'home_hero_trust_4_icon' => 'tag',
                'home_hero_image_alt_es' => 'Terrazas de Machu Picchu al amanecer',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('map', Setting::get('home_hero_trust_1_icon'));
        $this->assertSame('clock', Setting::get('home_hero_trust_2_icon'));
        $this->assertSame('Terrazas de Machu Picchu al amanecer', Setting::get('home_hero_image_alt_es'));
    }
}
