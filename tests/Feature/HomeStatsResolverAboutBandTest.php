<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Tour;
use App\Services\HomeStatsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Banda "Miles de viajeros ya confiaron en nosotros" (y su versión compacta
 * encimada al hero) de /nosotros.
 *
 * Bug original reportado: las 4 tarjetas salían en "0" en producción. Causa
 * raíz: el valor real SÍ se calculaba, pero solo se pintaba vía una
 * animación JS que arrancaba el HTML en "0" literal — si el JS no corría, el
 * cero quedaba fijo. Encima, dos de los cuatro números eran defaults
 * INVENTADOS (company_founded_year → 2015, happy_travelers_count → 5000)
 * que ningún cliente confirmó.
 *
 * Fix 2026-08-12 (medición real a 1440 + CRO): con esos defaults, la banda
 * SOLO mostraba 2 de 4 tiles reales ("24 Tours disponibles" y "8 Valores"),
 * dejando huecos enormes — no porque faltaran cifras inventadas, sino
 * porque había datos REALES sin usar (el mismo rating/reseñas que ya
 * publica el hero, y los destinos con tours publicados). Este archivo
 * documenta el orden NUEVO de los 5 slots:
 *   0 => rating_real         ("Valoración de viajeros", ej. "5.0")
 *   1 => reviews_count       ("Opiniones de viajeros", ej. "18")
 *   2 => tours_count         ("Tours & experiencias")
 *   3 => destinations_count  ("Destinos", regiones con tours publicados)
 *   4 => valores (fijo, conteo de tags de Misión/Visión/Valores)
 */
class HomeStatsResolverAboutBandTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): HomeStatsResolver
    {
        return app(HomeStatsResolver::class);
    }

    public function test_with_no_reviews_no_tours_and_no_destinations_only_the_values_slot_can_show(): void
    {
        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertFalse($band['slots'][0]['show']); // rating (sin reseñas)
        $this->assertFalse($band['slots'][1]['show']); // opiniones (sin reseñas)
        $this->assertFalse($band['slots'][2]['show']); // tours (sin tours publicados)
        $this->assertFalse($band['slots'][3]['show']); // destinos (sin regiones con tours)

        // Valores SÍ tiene un dato real (el conteo que la propia página
        // pinta en Misión/Visión/Valores) y debe mostrarse.
        $this->assertTrue($band['slots'][4]['show']);
        $this->assertSame('8', $band['slots'][4]['value']);
    }

    public function test_values_slot_hides_when_the_page_has_zero_value_tags(): void
    {
        $band = $this->resolver()->resolveAboutBand('es', 0);

        $this->assertFalse($band['slots'][4]['show']);
        $this->assertNull($band['slots'][4]['value']);
    }

    public function test_tours_count_slot_shows_the_real_published_count(): void
    {
        Tour::factory()->create(['is_published' => true]);
        Tour::factory()->create(['is_published' => true]);
        Tour::factory()->create(['is_published' => false]);

        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertTrue($band['slots'][2]['show']);
        $this->assertSame('2', $band['slots'][2]['value']);
    }

    /**
     * Fix 1 (Alto, docs/rebrand/LOTE-MOCKUPS-AGO-2026.md): la home ya
     * publica el rating/opiniones reales vía ReviewAggregator — antes
     * Nosotros no reutilizaba ese dato para nada. Reproduce el defecto:
     * si el slot 1 volviera a `years_active` (el default viejo, sin
     * `company_started_year` configurado), esto falla porque el slot queda
     * oculto en vez de mostrar el rating real.
     */
    public function test_rating_and_reviews_slots_reuse_the_same_review_aggregator_as_home(): void
    {
        Testimonial::factory()->create(['rating' => 5.0, 'is_active' => true]);
        Testimonial::factory()->create(['rating' => 5.0, 'is_active' => true]);

        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertTrue($band['slots'][0]['show']);
        $this->assertSame('5.0', $band['slots'][0]['value']);

        $this->assertTrue($band['slots'][1]['show']);
        $this->assertSame('2', $band['slots'][1]['value']);
    }

    /**
     * Fix 1: el sitio ya conoce sus destinos con tours publicados
     * (Region::scopeWithPublishedTours(), el mismo guard que usan las
     * tarjetas de destino de Home y Nosotros). Antes esta banda no
     * reutilizaba ese dato en ningún slot.
     */
    public function test_destinations_count_slot_shows_regions_with_at_least_one_published_tour(): void
    {
        $lima = Region::create(['slug' => 'lima', 'name_es' => 'Lima', 'is_active' => true, 'order' => 1]);
        $cusco = Region::create(['slug' => 'cusco', 'name_es' => 'Cusco', 'is_active' => true, 'order' => 2]);
        // Sin ningún tour publicado: no debe contar (mismo guard que el resto del sitio).
        Region::create(['slug' => 'arequipa', 'name_es' => 'Arequipa', 'is_active' => true, 'order' => 3]);

        Tour::factory()->create(['region_id' => $lima->id, 'is_published' => true]);
        Tour::factory()->create(['region_id' => $cusco->id, 'is_published' => true]);
        Tour::factory()->create(['region_id' => $cusco->id, 'is_published' => false]);

        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertTrue($band['slots'][3]['show']);
        $this->assertSame('2', $band['slots'][3]['value']);
    }

    public function test_destinations_count_slot_hides_when_no_region_has_a_published_tour(): void
    {
        Region::create(['slug' => 'lima', 'name_es' => 'Lima', 'is_active' => true, 'order' => 1]);

        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertFalse($band['slots'][3]['show']);
        $this->assertNull($band['slots'][3]['value']);
    }

    public function test_years_active_source_still_works_when_chosen_manually(): void
    {
        Setting::set('about_stat_1_source', 'years_active');
        Setting::set('company_started_year', (int) now()->format('Y') - 11);

        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertTrue($band['slots'][0]['show']);
        $this->assertSame('11+', $band['slots'][0]['value']);
    }

    /**
     * Regla del proyecto: no hay ninguna fuente calculada de "viajeros
     * atendidos" (docs/rebrand/ESTADO.md §3). El slot manual solo se
     * enciende con un número que el cliente confirme a mano — nunca un 5000
     * de fábrica. Se prueba eligiendo `manual` explícitamente en un slot,
     * ya que ningún default de la banda usa `manual` desde este fix.
     */
    public function test_manual_source_never_has_an_invented_default(): void
    {
        Setting::set('about_stat_2_source', 'manual');
        $band = $this->resolver()->resolveAboutBand('es', 8);
        $this->assertFalse($band['slots'][1]['show']);

        Setting::set('about_stat_2_value', '5,000+');
        $band = $this->resolver()->resolveAboutBand('es', 8);
        $this->assertTrue($band['slots'][1]['show']);
        $this->assertSame('5,000+', $band['slots'][1]['value']);
    }

    public function test_labels_and_icons_are_editable_per_slot(): void
    {
        Setting::set('about_stat_1_label_es', 'Reseñas destacadas');
        Setting::set('about_stat_5_label_es', 'Nuestros valores');

        $band = $this->resolver()->resolveAboutBand('es', 8);

        $this->assertSame('Reseñas destacadas', $band['slots'][0]['label']);
        $this->assertSame('Nuestros valores', $band['slots'][4]['label']);
    }

    public function test_about_stats_enabled_toggle_defaults_true_and_can_be_disabled(): void
    {
        $this->assertTrue($this->resolver()->resolveAboutBand('es', 8)['enabled']);

        Setting::set('about_stats_enabled', '0', 'boolean');
        $this->assertFalse($this->resolver()->resolveAboutBand('es', 8)['enabled']);
    }

    /**
     * El namespace `about_stat_*` es independiente del `home_stat_*` del
     * hero: configurar uno no debe afectar al otro.
     */
    public function test_about_band_settings_do_not_leak_into_the_hero_band(): void
    {
        Setting::set('about_stat_3_source', 'manual');
        Setting::set('about_stat_3_value', 'Solo para Nosotros');

        $home = $this->resolver()->resolve('es');
        // El slot 3 del hero (tours_count por defecto) sigue sin tours publicados.
        $this->assertFalse($home['slots'][2]['show']);
    }
}
