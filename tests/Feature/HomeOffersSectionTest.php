<?php

namespace Tests\Feature;

use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo #2 de docs/qa/panel-filament.md (F2, cro-validator):
 *
 * El recurso "Ofertas" (OfferResource, admin → Marketing) no tenía NINGÚN
 * consumidor en el front: HomeController ya calculaba `$offers` (activas,
 * sin vencer, orden manual, límite 3) y se lo pasaba a `home.blade.php`,
 * pero la vista nunca usaba esa variable. El editor podía publicar una
 * oferta completa y nunca se vería en el sitio.
 *
 * Se cableó una sección "Ofertas especiales" en home.blade.php que
 * consume `$offers` con datos reales del modelo (sin contenido inventado).
 * Este test confirma que una oferta activa aparece en `/es` y que una
 * inactiva o vencida no aparece (respeta el scope `Offer::active()`).
 */
class HomeOffersSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_offer_is_rendered_on_home(): void
    {
        Offer::create([
            'title_es' => 'QA_ Oferta activa de prueba',
            'cta_label_es' => 'Ver oferta',
            'is_active' => true,
            'order' => 0,
        ]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertSee('QA_ Oferta activa de prueba');
        $response->assertSee('Ver oferta');
    }

    public function test_inactive_offer_is_not_rendered_on_home(): void
    {
        Offer::create([
            'title_es' => 'QA_ Oferta inactiva de prueba',
            'cta_label_es' => 'Ver oferta',
            'is_active' => false,
            'order' => 0,
        ]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertDontSee('QA_ Oferta inactiva de prueba');
    }

    public function test_expired_offer_is_not_rendered_on_home(): void
    {
        Offer::create([
            'title_es' => 'QA_ Oferta vencida de prueba',
            'cta_label_es' => 'Ver oferta',
            'is_active' => true,
            'valid_until' => now()->subDay(),
            'order' => 0,
        ]);

        $response = $this->get('/es');

        $response->assertOk();
        $response->assertDontSee('QA_ Oferta vencida de prueba');
    }

    public function test_home_renders_fine_with_no_offers(): void
    {
        $response = $this->get('/es');

        $response->assertOk();
    }
}
