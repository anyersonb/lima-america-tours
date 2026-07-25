<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L1 — Salud técnica del Panel Filament (docs/qa/PROTOCOLO.md §3 L1).
 *
 * Verifica, autenticado como admin, que cada Resource/Page del panel carga
 * sin excepción (200) tanto en su listado (index) como en su formulario de
 * creación (create), y que las rutas de admin quedan protegidas sin sesión.
 *
 * Corre contra el entorno "testing" (sqlite en memoria): no toca
 * lima_america ni lima_america_qa. Cualquier excepción durante estas
 * peticiones queda también registrada en storage/logs/laravel.log (mismo
 * canal que local/qa), que se revisa aparte como evidencia L1.
 */
class PanelFilamentHealthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@limaamericatours.com',
        ]);
    }

    public function test_admin_panel_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_dashboard_loads_for_authenticated_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    }

    /**
     * @dataProvider resourceIndexProvider
     */
    public function test_resource_index_loads(string $routeName): void
    {
        $this->actingAs($this->admin())
            ->get(route($routeName))
            ->assertOk();
    }

    public static function resourceIndexProvider(): array
    {
        return [
            'abandoned-carts'        => ['filament.admin.resources.abandoned-carts.index'],
            'blocked-dates'          => ['filament.admin.resources.blocked-dates.index'],
            'blog-posts'             => ['filament.admin.resources.blog-posts.index'],
            'bookings'               => ['filament.admin.resources.bookings.index'],
            'categories'             => ['filament.admin.resources.categories.index'],
            'contact-leads'          => ['filament.admin.resources.contact-leads.index'],
            'media-assets'           => ['filament.admin.resources.media-assets.index'],
            'newsletter-subscribers' => ['filament.admin.resources.newsletter-subscribers.index'],
            'offers'                 => ['filament.admin.resources.offers.index'],
            'pages'                  => ['filament.admin.resources.pages.index'],
            'regions'                => ['filament.admin.resources.regions.index'],
            'testimonials'           => ['filament.admin.resources.testimonials.index'],
            'tours'                  => ['filament.admin.resources.tours.index'],
        ];
    }

    /**
     * @dataProvider resourceCreateProvider
     */
    public function test_resource_create_form_loads(string $routeName): void
    {
        $this->actingAs($this->admin())
            ->get(route($routeName))
            ->assertOk();
    }

    public static function resourceCreateProvider(): array
    {
        // AbandonedCartResource no tiene 'create' (canCreate() === false): correcto, se omite.
        return [
            'blocked-dates'          => ['filament.admin.resources.blocked-dates.create'],
            'blog-posts'             => ['filament.admin.resources.blog-posts.create'],
            'bookings'               => ['filament.admin.resources.bookings.create'],
            'categories'             => ['filament.admin.resources.categories.create'],
            'contact-leads'          => ['filament.admin.resources.contact-leads.create'],
            'media-assets'           => ['filament.admin.resources.media-assets.create'],
            'newsletter-subscribers' => ['filament.admin.resources.newsletter-subscribers.create'],
            'offers'                 => ['filament.admin.resources.offers.create'],
            'pages'                  => ['filament.admin.resources.pages.create'],
            'regions'                => ['filament.admin.resources.regions.create'],
            'testimonials'           => ['filament.admin.resources.testimonials.create'],
            'tours'                  => ['filament.admin.resources.tours.create'],
        ];
    }

    public function test_settings_page_loads(): void
    {
        $this->actingAs($this->admin())
            ->get(route('filament.admin.pages.settings'))
            ->assertOk();
    }

    public function test_maintenance_page_loads(): void
    {
        $this->actingAs($this->admin())
            ->get(route('filament.admin.pages.maintenance'))
            ->assertOk();
    }
}
