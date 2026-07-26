<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Petición del cliente (2026-07-25): el logo de los metadatos (JSON-LD
 * Organization/TravelAgency) debe ser el LOGO ORIGINAL del header
 * (assets/logos/logo-america-original.webp), no la recreación a color
 * (logo-america-red.png) que arrastraba el fork de Lima View.
 *
 * Fix: resources/views/components/jsonld.blade.php — propiedad 'logo'.
 *
 * Nota: no basta con assertSee('logo-america-original.webp') porque el
 * header ya sirve ese archivo y el aserto pasaría sin el fix. Estos tests
 * inspeccionan la propiedad 'logo' DENTRO del bloque JSON-LD, de modo que
 * fallan de verdad con el logo viejo y pasan con el corregido.
 */
class JsonLdLogoTest extends TestCase
{
    use RefreshDatabase;

    private function organizationLogo(): string
    {
        $html = $this->get('/es')->assertOk()->getContent();

        // Primer bloque application/ld+json = Organization/TravelAgency.
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m[1] ?? null, 'No se encontró el bloque JSON-LD.');

        $data = json_decode(trim($m[1]), true);
        $this->assertIsArray($data, 'El JSON-LD no es JSON válido.');

        return $data['logo'] ?? '';
    }

    public function test_jsonld_logo_is_the_original_header_logo(): void
    {
        $this->assertStringEndsWith(
            'assets/logos/logo-america-original.webp',
            $this->organizationLogo()
        );
    }

    public function test_jsonld_logo_is_not_the_red_recreation(): void
    {
        $this->assertStringNotContainsString(
            'logo-america-red.png',
            $this->organizationLogo()
        );
    }
}
