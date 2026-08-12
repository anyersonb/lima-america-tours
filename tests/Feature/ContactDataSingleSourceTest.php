<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote 2026-08-11: el sitio publicaba datos de contacto que no eran del
 * cliente, en tres idiomas a la vez:
 *
 *  - Teléfono +51 935 542 384 hardcodeado en legal.php (es/en/pt) — de
 *    NINGÚN cliente de esta agencia.
 *  - Dos direcciones simultáneas: el Setting traía la de Lima View Tours
 *    (Av. Larcomar 233), y footer.php/legal.php tenían de fallback una
 *    segunda (Jr. Lampa 209), tampoco confirmada.
 *  - Hasta 4 horarios distintos (panel, footer.php, ui.php, legal.php,
 *    contact.blade.php), ninguno igual a otro.
 *  - RUC contradictorio (20616108264 vs 10720481826), ninguno confirmado.
 *
 * Fix: App\Models\Setting::contactAddress()/contactHours()/companyRuc()/
 * companyLegalName() son la ÚNICA fuente, sin fallback a texto de idioma ni
 * a otro cliente. Cada consumidor oculta su fragmento cuando el dato falta,
 * en vez de imprimir una frase rota o un valor inventado.
 */
class ContactDataSingleSourceTest extends TestCase
{
    use RefreshDatabase;

    private const LEGACY_PHONE = '935 542 384';

    private const LEGACY_PHONE_DIGITS = '935542384';

    private const LEGACY_ADDRESS_LAMPA = 'Jr. Lampa 209';

    private const LEGACY_ADDRESS_LARCOMAR = 'Av. Larcomar 233';

    private function clearAllContactSettings(): void
    {
        foreach (['contact_address_es', 'contact_address_en', 'contact_address_pt',
            'contact_hours_es', 'contact_hours_en', 'contact_hours_pt',
            'contact_phone', 'whatsapp', 'company_ruc', 'company_legal_name'] as $key) {
            Setting::set($key, '');
        }
    }

    // ── App\Models\Setting helpers ──────────────────────────────────────

    public function test_contact_address_has_no_fallback_and_returns_null_when_empty(): void
    {
        $this->clearAllContactSettings();
        $this->assertNull(Setting::contactAddress('es'));
    }

    public function test_contact_address_falls_back_to_spanish_only(): void
    {
        $this->clearAllContactSettings();
        Setting::set('contact_address_es', 'Av. Real 123, Lima');

        $this->assertSame('Av. Real 123, Lima', Setting::contactAddress('en'));
    }

    public function test_contact_hours_has_no_fallback_and_returns_null_when_empty(): void
    {
        $this->clearAllContactSettings();
        $this->assertNull(Setting::contactHours('es'));
    }

    public function test_company_ruc_and_legal_name_have_no_invented_default(): void
    {
        $this->clearAllContactSettings();
        $this->assertNull(Setting::companyRuc());
        $this->assertNull(Setting::companyLegalName());

        Setting::set('company_ruc', '20123456789');
        $this->assertSame('20123456789', Setting::companyRuc());
    }

    // ── Términos / Privacidad: nunca el teléfono/dirección/horario heredados ──

    public function test_terms_page_never_prints_the_legacy_phone_in_any_locale(): void
    {
        $this->clearAllContactSettings();

        foreach (['es', 'en', 'pt'] as $locale) {
            $html = $this->get("/{$locale}/terminos")->assertOk()->getContent();
            $this->assertStringNotContainsString(self::LEGACY_PHONE, $html, "locale={$locale}");
            $this->assertStringNotContainsString(self::LEGACY_PHONE_DIGITS, $html, "locale={$locale}");
        }
    }

    public function test_terms_and_privacy_never_print_either_suspect_address_in_any_locale(): void
    {
        $this->clearAllContactSettings();
        // Incluso si alguien vuelve a cargar la dirección de Lima View por error,
        // la página de Términos no debe imprimirla igual (no hay ningún texto
        // fijo que la reemplace por otra tampoco).
        Setting::set('contact_address_es', self::LEGACY_ADDRESS_LARCOMAR.', Miraflores');

        foreach (['es', 'en', 'pt'] as $locale) {
            $terms = $this->get("/{$locale}/terminos")->assertOk()->getContent();
            $privacy = $this->get("/{$locale}/privacidad")->assertOk()->getContent();

            $this->assertStringNotContainsString(self::LEGACY_ADDRESS_LAMPA, $terms, "terms locale={$locale}");
            $this->assertStringNotContainsString(self::LEGACY_ADDRESS_LAMPA, $privacy, "privacy locale={$locale}");
        }
    }

    /**
     * El caso explícito que pide el jefe: sin ningún dato configurado, la
     * sección de contacto de Términos no debe imprimir una frase rota tipo
     * "llamando al ." — cada línea se omite por completo.
     */
    public function test_terms_contact_section_omits_lines_cleanly_when_nothing_is_configured(): void
    {
        $this->clearAllContactSettings();

        $html = $this->get('/es/terminos')->assertOk()->getContent();

        $this->assertStringNotContainsString('llamando al .', $html);
        $this->assertStringNotContainsString('llamando al  ', $html);
        $this->assertStringNotContainsString('atiende .', $html);
        $this->assertStringNotContainsString('Lima América Tours — .', $html);
    }

    public function test_terms_contact_section_prints_the_real_configured_data(): void
    {
        $this->clearAllContactSettings();
        Setting::set('contact_phone', '+51 999 111 222');
        Setting::set('contact_hours_es', 'Lun a Vie 9am-6pm');
        Setting::set('contact_address_es', 'Av. Confirmada 456, Lima');

        $html = $this->get('/es/terminos')->assertOk()->getContent();

        $this->assertStringContainsString('+51 999 111 222', $html);
        $this->assertStringContainsString('Lun a Vie 9am-6pm', $html);
        $this->assertStringContainsString('Av. Confirmada 456, Lima', $html);
    }

    public function test_privacy_page_omits_address_clause_when_not_configured(): void
    {
        $this->clearAllContactSettings();

        $html = $this->get('/es/privacidad')->assertOk()->getContent();

        $this->assertStringContainsString('nuestro correo electrónico.', $html);
        $this->assertStringNotContainsString('dirección física ()', $html);
    }

    public function test_privacy_page_includes_the_address_clause_when_configured(): void
    {
        $this->clearAllContactSettings();
        Setting::set('contact_address_es', 'Av. Confirmada 456, Lima');

        $html = $this->get('/es/privacidad')->assertOk()->getContent();

        $this->assertStringContainsString('Av. Confirmada 456, Lima', $html);
    }

    // ── Footer: horario oculto sin dato, visible con dato ─────────────────

    public function test_footer_hides_the_hours_line_when_not_configured(): void
    {
        $this->clearAllContactSettings();

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString('6:30 p.m.', $html);
    }

    public function test_footer_shows_the_configured_hours(): void
    {
        $this->clearAllContactSettings();
        Setting::set('contact_hours_es', 'Lun a Sáb 8am-9pm');

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('Lun a Sáb 8am-9pm', $html);
    }

    // ── Ficha de contacto: sin el 4to horario inventado ───────────────────

    public function test_contact_page_does_not_render_its_own_invented_hours_fallback(): void
    {
        $this->clearAllContactSettings();

        $html = $this->get('/es/contacto')->assertOk()->getContent();

        $this->assertStringNotContainsString('9:30 a.m. – 7:00 p.m.', $html);
    }

    // ── JSON-LD: sin "Lima" como calle inventada, con taxID cuando hay RUC ──

    private function organizationSchema(string $html): ?array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        foreach ($m[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (in_array('TravelAgency', (array) ($decoded['@type'] ?? []), true)) {
                return $decoded;
            }
        }

        return null;
    }

    public function test_jsonld_omits_street_address_when_nothing_is_configured(): void
    {
        $this->clearAllContactSettings();
        Setting::set('geo_street', '');

        $html = $this->get('/es')->assertOk()->getContent();
        $organization = $this->organizationSchema($html);

        $this->assertNotNull($organization);
        $this->assertArrayNotHasKey('streetAddress', $organization['address'] ?? [], 'streetAddress no debe existir sin dato real — "Lima" no es una calle.');
    }

    public function test_jsonld_includes_tax_id_when_ruc_is_configured(): void
    {
        $this->clearAllContactSettings();
        Setting::set('company_ruc', '20123456789');

        $html = $this->get('/es')->assertOk()->getContent();
        $organization = $this->organizationSchema($html);

        $this->assertSame('20123456789', $organization['taxID'] ?? null);
    }

    public function test_jsonld_omits_tax_id_when_ruc_is_not_configured(): void
    {
        $this->clearAllContactSettings();

        $html = $this->get('/es')->assertOk()->getContent();
        $organization = $this->organizationSchema($html);

        $this->assertArrayNotHasKey('taxID', $organization ?? []);
    }
}
