<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO S-02: routes/web.php redirigía (301) el slug legacy
 * "tour-de-dia-completo-al-oasis-de-huacachina-con-buggie-privado-canam-islas-ballestas-en-paracas"
 * hacia "tour-privado-huacachina-islas-ballestas-atardecer-buggy-can-am", un
 * slug que NO existe en la BD real → el redirect terminaba en 404 (un
 * "falso OK": el 301 en sí funcionaba, pero el destino estaba roto).
 *
 * El tour equivalente vivo es "tour-paracas-ica-huacachina" ("Full Day
 * Paracas - Ica - Huacachina"). Este test crea ESE slug (como lo tiene la
 * BD real) y verifica que la cadena completa 301 → 200 funciona, no solo
 * que el status code del primer salto sea 301.
 */
class LegacySlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const LEGACY_PATH = '/es/tours/detalle/tour-de-dia-completo-al-oasis-de-huacachina-con-buggie-privado-canam-islas-ballestas-en-paracas';

    public function test_legacy_slug_redirects_to_an_existing_live_tour(): void
    {
        Tour::factory()->create([
            'slug' => 'tour-paracas-ica-huacachina',
            'title_es' => 'Full Day Paracas - Ica - Huacachina',
            'is_published' => true,
        ]);

        $response = $this->get(self::LEGACY_PATH);

        $response->assertStatus(301);
        $response->assertRedirect('/es/tours/detalle/tour-paracas-ica-huacachina');

        // Sigue la cadena completa: el 301 debe aterrizar en 200, no en 404
        // (el defecto original: destino inexistente en BD).
        $this->followingRedirects()->get(self::LEGACY_PATH)->assertOk();
    }
}
