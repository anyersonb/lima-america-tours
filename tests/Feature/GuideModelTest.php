<?php

namespace Tests\Feature;

use App\Models\Guide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAREA 2 del lote 2026-08 (docs/proyecto/08-seo.md, hallazgo S-04): el
 * sitio no tenía ni una cara humana. `Guide` es el modelo de equipo/guías
 * real del cliente — la tabla arranca vacía a propósito, sin datos de
 * mentira sembrados por seeder/factory por defecto.
 */
class GuideModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_table_starts_empty_no_fake_seed_data(): void
    {
        $this->assertSame(0, Guide::count());
    }

    public function test_active_scope_only_returns_active_guides(): void
    {
        Guide::factory()->create(['name' => 'QA_ Guía activo', 'is_active' => true]);
        Guide::factory()->create(['name' => 'QA_ Guía inactivo', 'is_active' => false]);

        $active = Guide::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('QA_ Guía activo', $active->first()->name);
    }

    public function test_ordered_scope_respects_the_manual_order_column(): void
    {
        Guide::factory()->create(['name' => 'QA_ Segundo', 'order' => 2]);
        Guide::factory()->create(['name' => 'QA_ Primero', 'order' => 1]);

        $ordered = Guide::ordered()->get();

        $this->assertSame('QA_ Primero', $ordered->first()->name);
        $this->assertSame('QA_ Segundo', $ordered->last()->name);
    }

    public function test_role_and_bio_accessors_fall_back_to_spanish(): void
    {
        $guide = Guide::factory()->create([
            'role_es' => 'Guía de montaña',
            'role_en' => null,
            'bio_es' => 'Bio en español',
            'bio_en' => null,
        ]);

        app()->setLocale('en');
        $this->assertSame('Guía de montaña', $guide->role);
        $this->assertSame('Bio en español', $guide->bio);
        app()->setLocale('es');
    }

    public function test_photo_url_accessor_is_null_without_a_photo(): void
    {
        $guide = Guide::factory()->create(['photo' => null]);

        $this->assertNull($guide->photo_url);
    }

    public function test_languages_are_cast_to_an_array(): void
    {
        $guide = Guide::factory()->create(['languages' => ['Español', 'Inglés', 'Quechua']]);
        $guide->refresh();

        $this->assertIsArray($guide->languages);
        $this->assertSame(['Español', 'Inglés', 'Quechua'], $guide->languages);
    }

    /**
     * Mockup 02-nosotros pinta un ícono de Instagram y uno de WhatsApp por
     * guía, pero SOLO cuando hay un handle real cargado — nunca un ícono
     * roto/vacío. Sin dato, el accessor debe devolver null (guard que la
     * vista usa para no pintar el ícono).
     */
    public function test_instagram_url_accessor_is_null_without_a_handle(): void
    {
        $guide = Guide::factory()->create(['instagram_handle' => null]);
        $this->assertNull($guide->instagram_url);

        $guide->instagram_handle = '@lima.tours';
        $this->assertSame('https://instagram.com/lima.tours', $guide->instagram_url);
    }

    public function test_whatsapp_url_accessor_is_null_without_a_number(): void
    {
        $guide = Guide::factory()->create(['whatsapp_number' => null]);
        $this->assertNull($guide->whatsapp_url);

        $guide->whatsapp_number = '51987654321';
        $this->assertSame('https://wa.me/51987654321', $guide->whatsapp_url);
    }

    /**
     * El "dato de valor" (ej. el caso real de Samira, elogiada por nombre en
     * 15 de 20 reseñas) es opcional y localizado con el mismo fallback a
     * español que role/bio.
     */
    public function test_highlight_accessor_falls_back_to_spanish(): void
    {
        $guide = Guide::factory()->create([
            'highlight_es' => 'Mencionada en 15 de 20 reseñas.',
            'highlight_en' => null,
        ]);

        app()->setLocale('en');
        $this->assertSame('Mencionada en 15 de 20 reseñas.', $guide->highlight);
        app()->setLocale('es');
    }

    public function test_highlight_is_null_when_not_set(): void
    {
        $guide = Guide::factory()->create(['highlight_es' => null]);
        $this->assertNull($guide->highlight);
    }
}
