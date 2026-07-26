<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reporte del jefe (2026-07-26): en la sección "Síguenos" de la página 404 no
 * se veían las redes. Causa: el bloque traía círculos placeholder (<circle>),
 * enlaces muertos (href="#"), listaba "Vimeo" (que no es red del sitio) y NO
 * leía las Settings social_*. Fix: errors/404.blade.php pinta los íconos de
 * marca reales tomados de Settings (como el footer), y oculta el bloque si no
 * hay ninguna red configurada.
 */
class NotFoundSocialLinksTest extends TestCase
{
    use RefreshDatabase;

    private function seedSocials(): void
    {
        Setting::set('social_facebook', 'https://facebook.com/limaamericatours');
        Setting::set('social_instagram', 'https://instagram.com/limaamericatours');
        Setting::set('social_tiktok', 'https://tiktok.com/@limaamericatours');
        Setting::set('social_youtube', 'https://youtube.com/@limaamericatours');
        Cache::flush(); // Setting::get cachea 'settings.all'
    }

    public function test_404_renders_real_social_links_from_settings(): void
    {
        $this->seedSocials();

        $response = $this->get('/es/ruta-que-no-existe-xyz-404');

        $response->assertNotFound();
        $response->assertSee('Síguenos', false);
        // Enlaces reales, no placeholders
        $response->assertSee('https://facebook.com/limaamericatours', false);
        $response->assertSee('https://instagram.com/limaamericatours', false);
        $response->assertSee('aria-label="Facebook"', false);
        // Ya no quedan los defectos anteriores
        $response->assertDontSee('Vimeo', false);
        $response->assertDontSee('href="#"', false);
    }

    public function test_404_hides_follow_block_when_no_socials_configured(): void
    {
        // Sin Settings de redes (tabla limpia por RefreshDatabase)
        Cache::flush();

        $response = $this->get('/es/ruta-que-no-existe-xyz-404');

        $response->assertNotFound();
        $response->assertDontSee('Síguenos', false);
    }
}
