<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Tour;
use App\Services\HomeStatsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAREA 1 del lote 2026-08 (docs/proyecto/08-seo.md, hallazgo S-04): la
 * barra de stats del hero mostraba "4.9" / "50K+" / "100%" / "10+" como
 * texto literal, sin ninguna fuente verificable. HomeStatsResolver permite
 * que cada slot elija una fuente calculada en vez de texto libre.
 *
 * Contrato probado aquí (el que consume/consumirá el maquetador):
 *   HomeStatsResolver::resolve(string $locale): array{
 *       enabled: bool,
 *       slots: array<int, array{source, show, value, label, icon}>
 *   }
 * Un slot con `show === false` debe OCULTARSE en la vista — nunca imprimir
 * un 0 ni un guion.
 */
class HomeStatsResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): HomeStatsResolver
    {
        return app(HomeStatsResolver::class);
    }

    public function test_manual_source_uses_the_free_text_setting_with_a_default_fallback(): void
    {
        // 2026-08-02: la fuente por defecto ya NO es `manual` con una cifra
        // escrita en código (el slot 1 devolvía "4.9" sin que existiera ninguna
        // reseña). Ahora hay que pedir `manual` explícitamente.
        Setting::set('home_stat_1_source', 'manual');

        // Manual SIN texto cargado: no hay nada que mostrar y el slot se oculta.
        // Un hueco es honesto; un número inventado no.
        $slot = $this->resolver()->resolve('es')['slots'][0];
        $this->assertSame('manual', $slot['source']);
        $this->assertFalse($slot['show']);
        $this->assertNull($slot['value']);

        // Con texto cargado, manual lo respeta tal cual.
        Setting::set('home_stat_1_value', '4.95');
        $slot = $this->resolver()->resolve('es')['slots'][0];
        $this->assertTrue($slot['show']);
        $this->assertSame('4.95', $slot['value']);
    }

    public function test_tours_count_source_matches_tour_published_scope(): void
    {
        Tour::factory()->create(['is_published' => true]);
        Tour::factory()->create(['is_published' => true]);
        Tour::factory()->create(['is_published' => false]);

        Setting::set('home_stat_2_source', 'tours_count');

        $slot = $this->resolver()->resolve('es')['slots'][1];

        $this->assertTrue($slot['show']);
        $this->assertSame('2', $slot['value']);
        $this->assertSame((string) Tour::published()->count(), $slot['value']);
    }

    public function test_tours_count_source_hides_the_slot_when_there_are_no_published_tours(): void
    {
        Setting::set('home_stat_2_source', 'tours_count');

        $slot = $this->resolver()->resolve('es')['slots'][1];

        $this->assertFalse($slot['show']);
        $this->assertNull($slot['value']);
    }

    public function test_years_active_source_computes_from_company_started_year(): void
    {
        Setting::set('home_stat_4_source', 'years_active');
        Setting::set('company_started_year', (int) now()->format('Y') - 7);

        $slot = $this->resolver()->resolve('es')['slots'][3];

        $this->assertTrue($slot['show']);
        $this->assertSame('7+', $slot['value']);
    }

    public function test_years_active_source_hides_the_slot_without_a_valid_start_year(): void
    {
        Setting::set('home_stat_4_source', 'years_active');
        // Sin company_started_year cargado.
        $slot = $this->resolver()->resolve('es')['slots'][3];
        $this->assertFalse($slot['show']);
        $this->assertNull($slot['value']);

        // Año en el futuro tampoco es válido.
        Setting::set('company_started_year', (int) now()->format('Y') + 1);
        $slot = $this->resolver()->resolve('es')['slots'][3];
        $this->assertFalse($slot['show']);
    }

    public function test_rating_real_and_reviews_count_sources_use_the_review_aggregator(): void
    {
        Testimonial::factory()->create(['is_active' => true, 'rating' => 5]);
        Testimonial::factory()->create(['is_active' => true, 'rating' => 4]);
        Testimonial::factory()->create(['is_active' => false, 'rating' => 1]); // inactivo: no cuenta

        Setting::set('home_stat_1_source', 'rating_real');
        Setting::set('home_stat_2_source', 'reviews_count');

        $slots = $this->resolver()->resolve('es')['slots'];

        $this->assertTrue($slots[0]['show']);
        $this->assertSame('4.5', $slots[0]['value']);

        $this->assertTrue($slots[1]['show']);
        $this->assertSame('2', $slots[1]['value']);
    }

    /**
     * El caso explícito que pide el brief: tabla de reseñas vacía no debe
     * mostrar un "0" ni un "0.0" — el slot se oculta.
     */
    public function test_rating_real_and_reviews_count_hide_the_slot_when_there_are_no_reviews(): void
    {
        $this->assertSame(0, Testimonial::count());

        Setting::set('home_stat_1_source', 'rating_real');
        Setting::set('home_stat_2_source', 'reviews_count');

        $slots = $this->resolver()->resolve('es')['slots'];

        $this->assertFalse($slots[0]['show']);
        $this->assertNull($slots[0]['value']);

        $this->assertFalse($slots[1]['show']);
        $this->assertNull($slots[1]['value']);
    }

    /**
     * `rating_external` cubre el caso real de producción: la BD de la app
     * solo tiene 13 reseñas importadas, pero el sitio viejo trae un snapshot
     * auditable de Trustindex (5.0 sobre 255 opiniones Google+Tripadvisor)
     * con enlace a la ficha real. No se calcula — se carga a mano, pero a
     * diferencia de "manual" viaja con `count` y `url` verificables.
     */
    public function test_rating_external_source_reads_the_three_settings_and_exposes_the_url(): void
    {
        Setting::set('home_stat_1_source', 'rating_external');
        Setting::set('reviews_external_rating', '5.0');
        Setting::set('reviews_external_count', '255');
        Setting::set('reviews_external_url', 'https://www.google.com/maps/place/lima-america-tours');

        $slot = $this->resolver()->resolve('es')['slots'][0];

        $this->assertTrue($slot['show']);
        $this->assertSame('5.0', $slot['value']);
        $this->assertSame(255, $slot['count']);
        $this->assertSame('https://www.google.com/maps/place/lima-america-tours', $slot['url']);
    }

    public function test_rating_external_source_hides_the_slot_without_rating_or_count(): void
    {
        Setting::set('home_stat_1_source', 'rating_external');
        // Sin reviews_external_rating ni reviews_external_count cargados.
        $slot = $this->resolver()->resolve('es')['slots'][0];

        $this->assertFalse($slot['show']);
        $this->assertNull($slot['value']);
        $this->assertNull($slot['url']);
    }

    public function test_rating_external_source_still_shows_with_only_the_count_present(): void
    {
        Setting::set('home_stat_1_source', 'rating_external');
        Setting::set('reviews_external_count', '255');

        $slot = $this->resolver()->resolve('es')['slots'][0];

        $this->assertTrue($slot['show']);
        $this->assertSame('255', $slot['value']);
        $this->assertSame(255, $slot['count']);
    }

    public function test_other_sources_always_expose_null_count_and_url_for_a_stable_contract(): void
    {
        $slot = $this->resolver()->resolve('es')['slots'][0]; // manual por defecto
        $this->assertNull($slot['count']);
        $this->assertNull($slot['url']);
    }

    public function test_an_unknown_source_value_falls_back_to_manual_instead_of_breaking(): void
    {
        Setting::set('home_stat_3_source', 'algo-que-no-existe');
        Setting::set('home_stat_3_value', '100%');

        $slot = $this->resolver()->resolve('es')['slots'][2];

        // Una fuente desconocida degrada a `manual` sin reventar. Con texto
        // cargado se muestra ese texto; sin él se ocultaría, que también es un
        // final seguro (2026-08-02: los defaults en código ya no traen cifras
        // inventadas de las que tirar).
        $this->assertSame('manual', $slot['source']);
        $this->assertTrue($slot['show']);
        $this->assertSame('100%', $slot['value']);
    }

    public function test_home_stats_enabled_toggle_is_exposed_and_defaults_true(): void
    {
        $this->assertTrue($this->resolver()->resolve('es')['enabled']);

        Setting::set('home_stats_enabled', '0', 'boolean');
        $this->assertFalse($this->resolver()->resolve('es')['enabled']);
    }
}
