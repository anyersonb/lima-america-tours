<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fork data/tours-reales (Lima América Tours, forkeado del motor de Lima
 * View Tours): trece sitios hardcodeaban "+51 925 886 725" / "51925886725"
 * como fallback de 'contact_phone' / 'whatsapp' — el teléfono REAL de Lima
 * View Tours, otro cliente. En cuanto el Setting quedaba vacío, ese número
 * ajeno reaparecía en el sitio público, en el JSON-LD que lee Google y en
 * los correos transaccionales.
 *
 * Fix: App\Models\Setting::contactPhone()/whatsappNumber() no tienen
 * fallback — devuelven null si el cliente no cargó el dato — y cada
 * consumidor fue actualizado para fallar en seguro (ocultar el bloque,
 * omitir la propiedad del schema) en vez de imprimir un número inventado
 * o heredado.
 *
 * Este test cubre el escenario "Setting vacío" (estado real de un fork
 * recién creado, antes de que el cliente cargue sus datos) y confirma que
 * el número de Lima View Tours no aparece en ninguna superficie pública.
 */
class ContactPhoneNoFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const LEGACY_PHONE_DIGITS = '925886725';

    private const LEGACY_WHATSAPP = '51925886725';

    private function assertNoLegacyLimaViewPhone(string $html): void
    {
        $this->assertStringNotContainsString(self::LEGACY_PHONE_DIGITS, $html);
        $this->assertStringNotContainsString(self::LEGACY_WHATSAPP, $html);
    }

    public function test_jsonld_omits_telephone_property_when_contact_settings_are_empty(): void
    {
        Setting::set('contact_phone', '');
        Setting::set('whatsapp', '');

        $html = $this->get('/es')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $organization = null;
        foreach ($m[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (in_array('TravelAgency', (array) ($decoded['@type'] ?? []), true)) {
                $organization = $decoded;
                break;
            }
        }

        $this->assertNotNull($organization, 'No se encontró el bloque TravelAgency/LocalBusiness en el JSON-LD.');
        $this->assertArrayNotHasKey('telephone', $organization, 'El schema no debe declarar "telephone" cuando no hay dato — un valor vacío/falso es peor que omitir la propiedad.');

        $this->assertNoLegacyLimaViewPhone($html);
    }

    public function test_jsonld_includes_telephone_property_when_whatsapp_is_configured(): void
    {
        Setting::set('contact_phone', '+51 999 111 222');
        Setting::set('whatsapp', '51999111222');

        $html = $this->get('/es')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $organization = null;
        foreach ($m[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (in_array('TravelAgency', (array) ($decoded['@type'] ?? []), true)) {
                $organization = $decoded;
                break;
            }
        }

        $this->assertNotNull($organization);
        $this->assertSame('+51999111222', $organization['telephone'] ?? null);
    }

    public function test_home_does_not_render_any_whatsapp_button_when_settings_are_empty(): void
    {
        Setting::set('contact_phone', '');
        Setting::set('whatsapp', '');
        Tour::factory()->featured()->create(['price' => 100, 'is_published' => true]);

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString('wa.me/', $html, 'Ningún botón de WhatsApp (FAB global, hero, header) debe renderizarse sin un número real cargado.');
        $this->assertNoLegacyLimaViewPhone($html);
    }

    public function test_home_renders_whatsapp_buttons_when_whatsapp_setting_is_present(): void
    {
        Setting::set('whatsapp', '51999111222');
        Tour::factory()->featured()->create(['price' => 100, 'is_published' => true]);

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('wa.me/51999111222', $html);
    }

    public function test_footer_does_not_print_an_empty_tel_link_when_phone_is_missing(): void
    {
        Setting::set('contact_phone', '');
        Setting::set('whatsapp', '');

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="tel:"', $html, 'Un tel: vacío es peor que no imprimir el enlace de teléfono.');
        $this->assertNoLegacyLimaViewPhone($html);
    }

    public function test_footer_prints_the_real_phone_when_configured(): void
    {
        Setting::set('contact_phone', '+51 999 111 222');

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('+51 999 111 222', $html);
        $this->assertStringContainsString('href="tel:51999111222"', $html);
    }
}
