<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cierra el hallazgo #3/#4 de docs/qa/panel-filament.md: los bloques del CMS
 * (Page.blocks, slugs "contacto"/"nosotros") ahora se pintan en el front con
 * fallback al contenido de hoy cuando el admin no los ha cargado.
 *
 * Alcance real (confirmado contra los mockups aprobados en
 * docs/propuesta/exports/lat-07-contacto.jpeg y lat-02-nosotros.jpeg):
 * - Contacto: hero_eyebrow/hero_title/hero_lead (únicos campos con sección viva
 *   en el diseño aprobado; img_hero/img_collage_1..4 no tienen slot en el mockup).
 * - Nosotros: hero_eyebrow/hero_title/hero_lead/img_hero (ya estaban cableados)
 *   + el repeater "stats" (Misión/Visión/Valores/Equipo), que ahora alimenta la
 *   sección de tarjetas MVV ya existente en vez del contenido fijo.
 */
class PageBlocksCmsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders_cms_hero_blocks_when_present(): void
    {
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [
                'hero_eyebrow_es' => 'QA_ RESOLVEMOS TUS DUDAS',
                'hero_title_es' => 'QA_ Contáctanos Prueba',
                'hero_lead_es' => 'QA_ Este es el lead de prueba del hero de contacto.',
            ],
        ]);

        $response = $this->get('/es/contacto');

        $response->assertOk();
        $response->assertSee('QA_ RESOLVEMOS TUS DUDAS');
        $response->assertSee('QA_ Contáctanos Prueba');
        $response->assertSee('QA_ Este es el lead de prueba del hero de contacto.');
    }

    public function test_contact_page_falls_back_to_default_copy_when_blocks_empty(): void
    {
        Page::create([
            'slug' => 'contacto',
            'title_es' => 'Contacto',
            'is_published' => true,
            'blocks' => [],
        ]);

        $response = $this->get('/es/contacto');

        $response->assertOk();
        $response->assertSee('ESTAMOS PARA AYUDARTE');
        $response->assertSee('¿Tienes dudas o quieres armar un tour a tu medida?');
    }

    public function test_about_page_renders_cms_hero_blocks_when_present(): void
    {
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => [
                'hero_eyebrow_es' => 'QA_SYNC_TEST_NOSOTROS',
                'hero_title_es' => 'QA_ Somos Prueba',
                'hero_lead_es' => 'QA_ Lead de prueba de nosotros.',
            ],
        ]);

        $response = $this->get('/es/nosotros');

        $response->assertOk();
        $response->assertSee('QA_SYNC_TEST_NOSOTROS');
        $response->assertSee('QA_ Somos Prueba');
        $response->assertSee('QA_ Lead de prueba de nosotros.');
    }

    public function test_about_page_renders_cms_stats_repeater_in_mvv_section(): void
    {
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => [
                'stats' => [
                    ['title_es' => 'QA_ Misión Prueba', 'desc_es' => 'QA_ Descripción misión prueba.'],
                    ['title_es' => 'QA_ Visión Prueba', 'desc_es' => 'QA_ Descripción visión prueba.'],
                    ['title_es' => 'QA_ Valores Prueba', 'desc_es' => 'QA_ Descripción valores prueba.'],
                    ['title_es' => 'QA_ Equipo Prueba', 'desc_es' => 'QA_ Descripción equipo prueba.'],
                ],
            ],
        ]);

        $response = $this->get('/es/nosotros');

        $response->assertOk();
        $response->assertSee('QA_ Misión Prueba');
        $response->assertSee('QA_ Descripción misión prueba.');
        $response->assertSee('QA_ Visión Prueba');
        $response->assertSee('QA_ Valores Prueba');
        $response->assertSee('QA_ Equipo Prueba');
        $response->assertDontSee('Contribuir a la calidad del desarrollo de la industria turística');
    }

    public function test_about_page_falls_back_to_default_mvv_when_stats_empty(): void
    {
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'Nosotros',
            'is_published' => true,
            'blocks' => [],
        ]);

        $response = $this->get('/es/nosotros');

        $response->assertOk();
        $response->assertSee('Misión');
        $response->assertSee('Contribuir a la calidad del desarrollo de la industria turística');
    }
}
