<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo #1 de docs/qa/panel-filament.md (F2, cro-validator):
 *
 * El fix `81ba722` tradujo solo las COLUMNAS de tabla de Region, Category,
 * Testimonial, Offer, NewsletterSubscriber y ContactLead, pero dejó los
 * campos del formulario Crear/Editar en inglés ("Is active", "Order",
 * "Seo title"…). Filament genera el label por defecto a partir del nombre
 * de columna cuando no se llama a ->label() explícitamente.
 *
 * Este test renderiza la página "Crear" (HTTP real, autenticado) de cada
 * Resource afectado y confirma que el texto en español aparece en el HTML
 * y que el texto en inglés que Filament generaría por defecto ya no
 * aparece. Es la forma más directa de probar "lo que el editor ve",
 * sin acoplarse a la implementación interna del form schema.
 */
class PanelFormLabelsSpanishTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@limaamericatours.com',
        ]);
    }

    public function test_region_create_form_has_spanish_labels(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.regions.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Activo', $html);
        $this->assertStringContainsString('Orden', $html);
        $this->assertStringContainsString('SEO — Título', $html);
        $this->assertStringNotContainsString('Is active', $html);
        $this->assertStringNotContainsString('Seo title', $html);
    }

    public function test_category_create_form_has_spanish_labels(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.categories.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Activo', $html);
        $this->assertStringContainsString('Orden', $html);
        $this->assertStringNotContainsString('Is active', $html);
    }

    public function test_testimonial_create_form_has_spanish_labels(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.testimonials.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Calificación', $html);
        $this->assertStringContainsString('Origen', $html);
        $this->assertStringNotContainsString('Rating', $html);
        $this->assertStringNotContainsString('Source', $html);
    }

    public function test_offer_create_form_has_spanish_labels(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.offers.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Activo', $html);
        $this->assertStringContainsString('Orden', $html);
        $this->assertStringContainsString('Válida hasta', $html);
        $this->assertStringNotContainsString('Valid until', $html);
    }

    public function test_newsletter_subscriber_create_form_has_spanish_labels(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.newsletter-subscribers.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Correo', $html);
        $this->assertStringContainsString('Idioma', $html);
        $this->assertStringContainsString('Suscrito el', $html);
        $this->assertStringNotContainsString('Subscribed at', $html);
    }

    public function test_contact_lead_create_form_has_spanish_labels(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('filament.admin.resources.contact-leads.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Apellido', $html);
        $this->assertStringContainsString('Mensaje', $html);
        $this->assertStringContainsString('Archivado', $html);
        $this->assertStringNotContainsString('Lastname', $html);
        $this->assertStringNotContainsString('Is archived', $html);
    }
}
