<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote ago-2026 (Home/Nosotros/Blog/Contacto rediseñados): el jefe puso como
 * criterio de cierre que el copy que los maquetadores dejaron hardcodeado en
 * los 4 Blade sea administrable desde el panel. Cada grupo migrado tiene DOS
 * pruebas: el valor del panel se refleja en la página, y con el campo vacío
 * la vista cae al texto por defecto sin imprimir un hueco/etiqueta vacía.
 *
 * No cubre diseño/CSS (fuera de alcance de un test PHP): eso lo valida CRO
 * contra los 4 breakpoints aprobados.
 */
class AdminEditableCopyLoteAgo2026Test extends TestCase
{
    use RefreshDatabase;

    // ── HOME — "Viaja con confianza y vive la mejor experiencia" ───────────

    public function test_home_why_items_render_from_settings_repeater(): void
    {
        Setting::set('home_why_items', json_encode([
            ['title_es' => 'QA_ Razón uno', 'title_en' => 'QA_ Reason one', 'desc_es' => 'QA_ Bajada uno', 'desc_en' => 'QA_ Sub one'],
            ['title_es' => 'QA_ Razón dos', 'title_en' => 'QA_ Reason two', 'desc_es' => 'QA_ Bajada dos', 'desc_en' => 'QA_ Sub two'],
        ]));

        $es = $this->get('/es')->assertOk();
        $es->assertSee('QA_ Razón uno');
        $es->assertSee('QA_ Bajada uno');
        $es->assertDontSee('Guías certificados');

        $en = $this->get('/en')->assertOk();
        $en->assertSee('QA_ Reason two');
        $en->assertSee('QA_ Sub two');
    }

    public function test_home_why_items_fall_back_to_the_five_defaults_when_empty(): void
    {
        $response = $this->get('/es')->assertOk();

        $response->assertSee('Guías certificados');
        $response->assertSee('Mejor precio garantizado');
        $response->assertSee('Cancelación flexible');
    }

    // ── NOSOTROS — checks "¿Por qué viajar con Lima América Tours?" ────────

    public function test_about_why_travel_checks_render_from_page_blocks(): void
    {
        Guide::factory()->create(['is_active' => true]);
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => [
                'why_travel_items' => [
                    ['text_es' => 'QA_ Check uno', 'text_pt' => 'QA_ Check um'],
                    ['text_es' => 'QA_ Check dos', 'text_pt' => 'QA_ Check dois'],
                ],
            ],
        ]);

        $es = $this->get('/es/nosotros')->assertOk();
        $es->assertSee('QA_ Check uno');
        $es->assertSee('QA_ Check dos');
        $es->assertDontSee('Guías certificados');

        $pt = $this->get('/pt/nosotros')->assertOk();
        $pt->assertSee('QA_ Check um');
        $pt->assertSee('QA_ Check dois');
    }

    public function test_about_why_travel_checks_fall_back_to_defaults_when_empty(): void
    {
        Guide::factory()->create(['is_active' => true]);
        Page::create(['slug' => 'nosotros', 'title_es' => 'Nosotros', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/nosotros')->assertOk();

        $response->assertSee('Guías certificados');
        $response->assertSee('Grupos pequeños');
    }

    public function test_about_contact_hours_still_appended_after_the_checks_repeater(): void
    {
        // El horario NUNCA se migra al repeater: sigue viniendo de
        // Configuración → Contacto (Setting::contactHours, única fuente).
        Guide::factory()->create(['is_active' => true]);
        Setting::set('contact_hours_es', 'QA_ Lunes a domingo 9-6pm');
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => ['why_travel_items' => [['text_es' => 'QA_ Check único']]],
        ]);

        $response = $this->get('/es/nosotros')->assertOk();

        $response->assertSee('QA_ Check único');
        $response->assertSee('QA_ Lunes a domingo 9-6pm');
    }

    // ── NOSOTROS — 4 features del CTA final ─────────────────────────────────

    public function test_about_cta_features_render_from_page_blocks(): void
    {
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => [
                'cta_feat1_fallback_es' => 'QA_ Escríbenos sin horario',
                'cta_feat1_label_es' => 'QA_ Etiqueta horario',
                'cta_feat2_title_es' => 'QA_ Feature 2 título',
                'cta_feat2_desc_es' => 'QA_ Feature 2 bajada',
                'cta_feat3_title_es' => 'QA_ Feature 3 título',
                'cta_feat3_desc_es' => 'QA_ Feature 3 bajada',
                'cta_feat4_title_es' => 'QA_ Feature 4 título',
                'cta_feat4_desc_es' => 'QA_ Feature 4 bajada',
            ],
        ]);

        $response = $this->get('/es/nosotros')->assertOk();

        // Sin horario cargado en Configuración → Contacto: se ve el fallback.
        $response->assertSee('QA_ Escríbenos sin horario');
        $response->assertSee('QA_ Etiqueta horario');
        $response->assertSee('QA_ Feature 2 título');
        $response->assertSee('QA_ Feature 2 bajada');
        $response->assertSee('QA_ Feature 3 título');
        $response->assertSee('QA_ Feature 4 título');
        $response->assertDontSee('Viajes 100% personalizados');
    }

    public function test_about_cta_feature_one_prefers_real_contact_hours_over_the_fallback_text(): void
    {
        Setting::set('contact_hours_es', 'QA_ 9:00–18:30');
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => ['cta_feat1_fallback_es' => 'QA_ Este texto NO debe verse'],
        ]);

        $response = $this->get('/es/nosotros')->assertOk();

        $response->assertSee('QA_ 9:00–18:30');
        $response->assertDontSee('QA_ Este texto NO debe verse');
    }

    public function test_about_cta_features_fall_back_to_defaults_when_blocks_empty(): void
    {
        Page::create(['slug' => 'nosotros', 'title_es' => 'Nosotros', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/nosotros')->assertOk();

        $response->assertSee('Viajes 100% personalizados');
        $response->assertSee('Seguridad y confianza');
        $response->assertSee('Cancelación flexible');
    }

    // ── NOSOTROS — etiquetas de la fila de confianza ────────────────────────

    public function test_about_trust_row_labels_render_from_page_blocks(): void
    {
        Testimonial::factory()->create(['is_active' => true, 'rating' => 5]);
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => [
                'trust_label_google_es' => 'QA_ Reseñas Google custom',
                'trust_label_tripadvisor_es' => 'QA_ Tripadvisor custom',
            ],
        ]);

        $response = $this->get('/es/nosotros')->assertOk();

        $response->assertSee('QA_ Reseñas Google custom');
        $response->assertSee('QA_ Tripadvisor custom');
        $response->assertDontSee('Reseñas de Google');
    }

    public function test_about_trust_row_labels_fall_back_to_defaults_when_empty(): void
    {
        Testimonial::factory()->create(['is_active' => true, 'rating' => 5]);
        Page::create(['slug' => 'nosotros', 'title_es' => 'Nosotros', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/nosotros')->assertOk();

        $response->assertSee('Reseñas de Google');
        $response->assertSee('Presencia en Tripadvisor');
    }

    // ── CONTACTO — 3 chips del hero ──────────────────────────────────────────

    public function test_contact_chips_render_from_page_blocks(): void
    {
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [
                'chip1_title_es' => 'QA_ Chip1 título', 'chip1_desc_es' => 'QA_ Chip1 bajada',
                'chip2_title_es' => 'QA_ Chip2 título', 'chip2_desc_es' => 'QA_ Chip2 bajada',
                'chip3_title_es' => 'QA_ Chip3 título', 'chip3_desc_es' => 'QA_ Chip3 bajada',
            ],
        ]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('QA_ Chip1 título');
        $response->assertSee('QA_ Chip2 bajada');
        $response->assertSee('QA_ Chip3 título');
        $response->assertDontSee('Respuesta rápida');
    }

    public function test_contact_chips_fall_back_to_defaults_when_empty(): void
    {
        Page::create(['slug' => 'contacto', 'title_es' => 'Contacto', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('Respuesta rápida');
        $response->assertSee('Atención personalizada');
        $response->assertSee('Viaja con confianza');
    }

    // ── CONTACTO — cabecera del formulario y placeholders ───────────────────

    public function test_contact_form_header_and_placeholders_render_from_page_blocks(): void
    {
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [
                'form_title_es' => 'QA_ Título formulario',
                'form_desc_es' => 'QA_ Descripción formulario',
                'ph_nombre_es' => 'QA_ placeholder nombre',
                'ph_asunto_es' => 'QA_ placeholder asunto',
                'ph_mensaje_es' => 'QA_ placeholder mensaje',
            ],
        ]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('QA_ Título formulario');
        $response->assertSee('QA_ Descripción formulario');
        $response->assertSee('QA_ placeholder nombre', false);
        $response->assertSee('QA_ placeholder asunto', false);
        $response->assertSee('QA_ placeholder mensaje', false);
    }

    public function test_contact_form_header_and_placeholders_fall_back_to_defaults_when_empty(): void
    {
        Page::create(['slug' => 'contacto', 'title_es' => 'Contacto', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('Envíanos un mensaje');
        $response->assertSee('Ej. María López', false);
        $response->assertSee('¿En qué podemos ayudarte?', false);
    }

    // ── CONTACTO — card de asesor ────────────────────────────────────────────

    public function test_contact_advisor_card_renders_from_page_blocks(): void
    {
        Tour::factory()->create();
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [
                'advisor_title_es' => 'QA_ Título asesor',
                'advisor_desc_es' => 'QA_ Descripción asesor',
                'advisor_cta_es' => 'QA_ Botón asesor',
            ],
        ]);
        Setting::set('whatsapp', '51900000000');

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('QA_ Título asesor');
        $response->assertSee('QA_ Descripción asesor');
        $response->assertSee('QA_ Botón asesor');
    }

    public function test_contact_advisor_card_falls_back_to_defaults_when_empty(): void
    {
        Tour::factory()->create();
        Page::create(['slug' => 'contacto', 'title_es' => 'Contacto', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('¿Necesitas ayuda para elegir tu tour?');
    }

    // ── CONTACTO — franja inferior ───────────────────────────────────────────

    public function test_contact_bottom_band_renders_from_page_blocks(): void
    {
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [
                'bottom_title_es' => 'QA_ Título franja',
                'bottom_desc_es' => 'QA_ Bajada franja',
                'bottom_cta_es' => 'QA_ Botón franja',
            ],
        ]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('QA_ Título franja');
        $response->assertSee('QA_ Bajada franja');
        $response->assertSee('QA_ Botón franja');
        $response->assertDontSee('¿Listo para tu próxima aventura?');
    }

    public function test_contact_bottom_band_falls_back_to_defaults_when_empty(): void
    {
        Page::create(['slug' => 'contacto', 'title_es' => 'Contacto', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('¿Listo para tu próxima aventura?');
    }

    // ── CONTACTO — etiquetas de canales + nota de punto de recojo ───────────

    public function test_contact_channel_labels_render_from_page_blocks(): void
    {
        Setting::set('contact_phone', '+51 900 000 000');
        Setting::set('contact_hours_es', 'QA_ 9 a 6');
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [
                'channel_phone_label_es' => 'QA_ Etiqueta teléfono',
                'channel_email_label_es' => 'QA_ Etiqueta correo',
                'channel_hours_label_es' => 'QA_ Etiqueta horario',
                'channel_pickup_label_es' => 'QA_ Etiqueta recojo',
                'channel_pickup_note_es' => 'QA_ Nota de recojo custom',
            ],
        ]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('QA_ Etiqueta teléfono');
        $response->assertSee('QA_ Etiqueta correo');
        $response->assertSee('QA_ Etiqueta horario');
        $response->assertSee('QA_ Etiqueta recojo');
        $response->assertSee('QA_ Nota de recojo custom');
        $response->assertDontSee('Teléfono / WhatsApp');
    }

    public function test_contact_channel_labels_fall_back_to_defaults_when_empty(): void
    {
        Setting::set('contact_phone', '+51 900 000 000');
        Page::create(['slug' => 'contacto', 'title_es' => 'Contacto', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('Teléfono / WhatsApp');
        $response->assertSee('Correo');
        $response->assertSee('Punto de recojo');
    }

    // ── CONTACTO — alt de la foto del hero ──────────────────────────────────

    public function test_contact_hero_image_alt_renders_from_page_blocks(): void
    {
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => ['hero_image_alt_es' => 'QA_ Alt personalizado del hero'],
        ]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('QA_ Alt personalizado del hero', false);
    }

    public function test_contact_hero_image_alt_falls_back_to_default_when_empty(): void
    {
        Page::create(['slug' => 'contacto', 'title_es' => 'Contacto', 'is_published' => true, 'blocks' => []]);

        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee('Faro de Miraflores', false);
    }

    // ── BLOG — hero, buscador, título de sección y CTA final ────────────────

    public function test_blog_copy_renders_from_settings(): void
    {
        Setting::set('blog_hero_eyebrow_es', 'QA_ Eyebrow blog');
        Setting::set('blog_hero_title_es', 'QA_ Título blog');
        Setting::set('blog_hero_sub_es', 'QA_ Bajada blog');
        Setting::set('blog_search_placeholder_es', 'QA_ Buscar cosas');
        Setting::set('blog_toolbar_title_es', 'QA_ Explora esto');
        Setting::set('blog_cta_title_es', 'QA_ CTA título');
        Setting::set('blog_cta_desc_es', 'QA_ CTA bajada');
        Setting::set('blog_cta_btn_primary_es', 'QA_ CTA botón rojo');

        $response = $this->get('/es/blog')->assertOk();

        $response->assertSee('QA_ Eyebrow blog');
        $response->assertSee('QA_ Título blog');
        $response->assertSee('QA_ Bajada blog');
        $response->assertSee('QA_ Buscar cosas', false);
        $response->assertSee('QA_ Explora esto');
        $response->assertSee('QA_ CTA título');
        $response->assertSee('QA_ CTA bajada');
        $response->assertSee('QA_ CTA botón rojo');
        $response->assertDontSee('Inspírate para viajar');
    }

    public function test_blog_copy_falls_back_to_defaults_when_settings_empty(): void
    {
        $response = $this->get('/es/blog')->assertOk();

        $response->assertSee('Inspírate para viajar');
        $response->assertSee('Blog de viajes');
        $response->assertSee('Explora nuestros artículos');
        $response->assertSee('¿Listo para vivir tu propia historia?');
    }
}
