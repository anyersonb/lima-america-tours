<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suite de humo del protocolo de QA (docs/qa/PROTOCOLO.md §6).
 *
 * Se corre en CADA verificación de módulo, antes de mergear a main:
 * confirma que las rutas públicas responden, que el admin está protegido,
 * que la 404 rebrandeada existe, y que lo que se guarda en el CMS aparece
 * en el front (sincronía CMS→front).
 *
 * Corre contra el entorno "testing" (sqlite en memoria, ver phpunit.xml):
 * nunca toca lima_america ni lima_america_qa.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    /**
     * Public pages render the shared layout (social links, JSON-LD, etc.),
     * which reads baseline data seeded by DatabaseSeeder in every real
     * environment (local/qa/production). We seed it here too so the smoke
     * suite exercises the same conditions instead of a DB emptier than any
     * environment that actually serves traffic.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @dataProvider publicRouteProvider
     */
    public function test_public_route_returns_ok(string $path): void
    {
        $response = $this->get('/'.self::LOCALE.$path);

        $response->assertOk();
    }

    public static function publicRouteProvider(): array
    {
        return [
            'home'     => [''],
            'tours'    => ['/tours'],
            'blog'     => ['/blog'],
            'nosotros' => ['/nosotros'],
            'contacto' => ['/contacto'],
        ];
    }

    public function test_admin_panel_redirects_when_not_authenticated(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    public function test_unknown_route_returns_not_found(): void
    {
        $response = $this->get('/'.self::LOCALE.'/no-existe');

        $response->assertNotFound();
    }

    /**
     * Sincronía CMS → front: un Tour creado (p. ej. vía Filament) debe
     * aparecer en el listado público de tours con su título en español.
     */
    public function test_tour_created_via_factory_is_visible_on_public_tours_listing(): void
    {
        $tour = Tour::factory()->published()->create([
            'title_es' => 'Tour de prueba sincronía CMS-Front',
        ]);

        $response = $this->get('/'.self::LOCALE.'/tours');

        $response->assertOk();
        $response->assertSee($tour->title_es);
    }
}
