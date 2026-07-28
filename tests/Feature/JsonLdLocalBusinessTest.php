<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO S-02 (08-seo.md): el schema TravelAgency/LocalBusiness salía con
 * "name":"", "priceRange":"" y "address" sin streetAddress porque
 * resources/views/components/jsonld.blade.php usaba "??" para los
 * fallbacks — y esas claves de Settings existen en BD con valor '' (string
 * vacío), no null, así que "??" nunca disparaba el default (solo lo hace
 * con null/ausente).
 *
 * Este test reproduce EXACTAMENTE ese escenario (claves presentes con '')
 * para que falle de verdad con el bug viejo y pase con el fix ("?:").
 */
class JsonLdLocalBusinessTest extends TestCase
{
    use RefreshDatabase;

    private function organization(): array
    {
        $html = $this->get('/es')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m[1][0] ?? null, 'No se encontró ningún bloque JSON-LD.');

        $data = json_decode(trim($m[1][0]), true);
        $this->assertIsArray($data, 'El primer bloque JSON-LD no es JSON válido.');
        $this->assertSame(['TravelAgency', 'LocalBusiness'], $data['@type'] ?? null);

        return $data;
    }

    public function test_local_business_schema_is_not_empty_when_settings_hold_empty_strings(): void
    {
        // Reproduce el estado real reportado en BD: claves presentes, valor ''.
        Setting::set('geo_business_name', '');
        Setting::set('geo_price_range', '');
        Setting::set('geo_street', '');
        Setting::set('geo_city', '');
        Setting::set('geo_region', '');
        Setting::set('geo_country', '');
        Setting::set('site_name', 'Lima América Tours');
        Setting::set('contact_address_es', 'Av. Larcomar 233, Of. 410 — Miraflores, Lima');

        $org = $this->organization();

        $this->assertNotSame('', $org['name'] ?? '', 'name no debe quedar vacío');
        $this->assertNotSame('', $org['priceRange'] ?? '', 'priceRange no debe quedar vacío');
        $this->assertSame('Lima América Tours', $org['name']);
        $this->assertSame('$$', $org['priceRange']);

        $address = $org['address'] ?? [];
        $this->assertNotSame('', $address['streetAddress'] ?? '', 'address.streetAddress no debe quedar vacío');
        $this->assertSame('Av. Larcomar 233, Of. 410 — Miraflores, Lima', $address['streetAddress']);
        $this->assertNotSame('', $address['addressLocality'] ?? '');
        $this->assertNotSame('', $address['addressCountry'] ?? '');
    }

    public function test_website_search_action_targets_the_route_that_actually_filters(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $website = null;
        foreach ($m[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (($decoded['@type'] ?? null) === 'WebSite') {
                $website = $decoded;
                break;
            }
        }

        $this->assertNotNull($website, 'No se encontró el bloque WebSite en el JSON-LD.');
        $this->assertStringContainsString(
            '/es/buscar?q=',
            $website['potentialAction']['target'] ?? '',
            'El SearchAction debe apuntar a /es/buscar (tours.results), la ruta que sí filtra por "q" en TourController@search.'
        );
    }
}
