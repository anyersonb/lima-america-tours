<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Models\BlogPost;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cuatro campos de CMS nuevos para el artículo de blog
 * (docs/rebrand/inventario/spec-03-blog.md §9, resumido en el brief de este
 * lote): `features` (tarjeta de 4 bloques sobre el hero), `guide_id` (autor
 * real, con precedencia sobre los campos sueltos author_name/author_role/
 * author_photo agregados el 2026-08-19), `video_url` (botón de play del
 * hero) y `quote_text_*`/`quote_attribution` (cita destacada).
 *
 * Ningún post real tiene hoy dato en ninguno de estos campos — cada bloque
 * de tests cubre tanto el caso "hay dato" como el caso "no hay dato", porque
 * la regla dura del proyecto es que un componente sin dato real se oculta,
 * nunca publica un valor inventado (mismo criterio que "10 años", "Desde
 * $200", "4.8 (0 reseñas)").
 */
class BlogPostSpec03BlogFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $attributes = []): BlogPost
    {
        return BlogPost::create($attributes + [
            'title_es' => 'QA_ Artículo de prueba spec-03',
            'excerpt_es' => 'Extracto de prueba.',
            'body_es' => 'Cuerpo de prueba.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // features → feature_cards (§2: nunca asumir 4)
    // ────────────────────────────────────────────────────────────────

    /**
     * Con las 4 filas completas, feature_cards() devuelve las 4 en el
     * idioma activo (no solo español), en el mismo orden.
     */
    public function test_feature_cards_resolves_all_four_rows_in_the_active_locale(): void
    {
        app()->setLocale('en');

        $post = $this->makePost([
            'features' => [
                ['icon' => 'fork', 'title_es' => 'Cultura', 'title_en' => 'Culture', 'text_es' => 'ES1', 'text_en' => 'EN1'],
                ['icon' => 'star', 'title_es' => 'Calidad', 'title_en' => 'Quality', 'text_es' => 'ES2', 'text_en' => 'EN2'],
                ['icon' => 'pin', 'title_es' => 'Ubicación', 'title_en' => 'Location', 'text_es' => 'ES3', 'text_en' => 'EN3'],
                ['icon' => 'camera', 'title_es' => 'Fotos', 'title_en' => 'Photos', 'text_es' => 'ES4', 'text_en' => 'EN4'],
            ],
        ]);

        $cards = $post->feature_cards;

        $this->assertCount(4, $cards);
        $this->assertSame('Culture', $cards[0]['title']);
        $this->assertSame('EN1', $cards[0]['text']);
        $this->assertSame('fork', $cards[0]['icon']);
        $this->assertSame('Photos', $cards[3]['title']);
    }

    /**
     * El requisito explícito del brief: la tarjeta tiene que verse bien con
     * menos de 4 bloques. Con solo 2 filas cargadas, feature_cards() debe
     * devolver exactamente 2 — nunca rellenar hasta 4 con vacíos.
     */
    public function test_feature_cards_returns_fewer_than_four_when_the_editor_only_filled_two(): void
    {
        $post = $this->makePost([
            'features' => [
                ['icon' => 'fork', 'title_es' => 'Cultura', 'text_es' => 'Uno'],
                ['icon' => 'star', 'title_es' => 'Calidad', 'text_es' => 'Dos'],
            ],
        ]);

        $this->assertCount(2, $post->feature_cards);
    }

    /**
     * Una fila sin título (el editor dejó el bloque a medio llenar) se
     * descarta en vez de mostrarse en blanco.
     */
    public function test_feature_cards_drops_rows_without_a_title(): void
    {
        $post = $this->makePost([
            'features' => [
                ['icon' => 'fork', 'title_es' => 'Cultura', 'text_es' => 'Uno'],
                ['icon' => 'star', 'title_es' => '', 'text_es' => 'Sin título'],
            ],
        ]);

        $this->assertCount(1, $post->feature_cards);
        $this->assertSame('Cultura', $post->feature_cards[0]['title']);
    }

    public function test_feature_cards_is_empty_when_the_column_has_no_data(): void
    {
        $this->assertSame([], $this->makePost()->feature_cards);
    }

    /**
     * Un idioma sin traducción cargada cae al título/texto en español, igual
     * que title/excerpt/body — consistencia con el resto del modelo.
     */
    public function test_feature_cards_falls_back_to_spanish_when_the_active_locale_has_no_translation(): void
    {
        app()->setLocale('pt');

        $post = $this->makePost([
            'features' => [
                ['icon' => 'fork', 'title_es' => 'Cultura', 'text_es' => 'Texto en español'],
            ],
        ]);

        $this->assertSame('Cultura', $post->feature_cards[0]['title']);
        $this->assertSame('Texto en español', $post->feature_cards[0]['text']);
    }

    // ────────────────────────────────────────────────────────────────
    // guide_id vs. author_name/author_role/author_photo (§5.1, precedencia)
    // ────────────────────────────────────────────────────────────────

    /**
     * Regla de precedencia decidida para este lote: si hay un guía real
     * enlazado, GANA POR COMPLETO sobre los tres campos sueltos — nunca se
     * mezclan campo por campo (nombre del guía + foto suelta, etc.).
     */
    public function test_signature_uses_the_linked_guide_completely_even_when_loose_fields_are_also_set(): void
    {
        $guide = Guide::factory()->create([
            'name' => 'Augusto Real',
            'role_es' => 'Guía Local',
            'photo' => 'guides/augusto.jpg',
        ]);

        $post = $this->makePost([
            'guide_id' => $guide->id,
            // Campos sueltos intencionalmente distintos, para probar que NO se usan.
            'author_name' => 'Nombre suelto que no debería verse',
            'author_role' => 'Rol suelto que no debería verse',
        ]);

        $this->assertSame('Augusto Real', $post->signature_name);
        $this->assertSame('Guía Local', $post->signature_role);
        $this->assertNotNull($post->signature_photo_url);
        $this->assertStringContainsString('guides/augusto.jpg', $post->signature_photo_url);
        $this->assertTrue($post->signature_is_verified);
    }

    /**
     * Sin guía enlazado, la firma cae a los campos sueltos exactamente como
     * ya funcionaban antes de este lote (BlogPostNewMockupFieldsTest).
     */
    public function test_signature_falls_back_to_loose_fields_when_no_guide_is_linked(): void
    {
        $post = $this->makePost([
            'author_name' => 'Augusto',
            'author_role' => 'Guía Local',
        ]);

        $this->assertSame('Augusto', $post->signature_name);
        $this->assertSame('Guía Local', $post->signature_role);
        $this->assertNull($post->signature_photo_url);
        $this->assertFalse($post->signature_is_verified);
    }

    /**
     * El check de "verificado" es exclusivo del guía real enlazado — nunca
     * true solo porque alguien escribió un nombre en el campo suelto (spec
     * §5.1 / 02-tour-y-blog.md §2.B #10).
     */
    public function test_signature_is_verified_is_false_for_a_typed_author_name_without_a_linked_guide(): void
    {
        $post = $this->makePost(['author_name' => 'Cualquiera']);

        $this->assertFalse($post->signature_is_verified);
    }

    /**
     * `guide_id` usa `nullOnDelete()`: en MySQL (confirmado a mano contra
     * `lima_america` local — `information_schema.REFERENTIAL_CONSTRAINTS`
     * reporta `DELETE_RULE = SET NULL` para esta FK) borrar el guía deja la
     * columna en null de verdad. La suite de tests corre sobre SQLite en
     * memoria (`phpunit.xml`), que NO aplica el `ON DELETE` de un FK
     * agregado por `Schema::table()`/ALTER — limitación conocida del motor
     * de test, no de la migración — así que acá `guide_id` puede quedar
     * "colgado" apuntando a un guía que ya no existe. Por eso el accesor no
     * confía solo en que la columna sea null: `resolvedGuide()` resuelve la
     * relación `guide` de verdad, que en Eloquent devuelve null cuando la
     * fila referenciada no existe, sea porque el FK la limpió (MySQL) o
     * porque simplemente ya no hay fila que hacer match (SQLite). Este test
     * verifica esa robustez a nivel de modelo, que es la que importa para
     * que la firma no rompa en ningún motor: la firma debe caer de vuelta a
     * los campos sueltos, nunca quedar apuntando a un guía fantasma ni
     * mostrar el check de verificado.
     */
    public function test_signature_falls_back_after_the_linked_guide_is_deleted(): void
    {
        $guide = Guide::factory()->create(['name' => 'Augusto Real']);

        $post = $this->makePost([
            'guide_id' => $guide->id,
            'author_name' => 'Respaldo tras borrar al guía',
        ]);

        $guide->delete();
        $post->refresh();
        $post->load('guide');

        $this->assertSame('Respaldo tras borrar al guía', $post->signature_name);
        $this->assertFalse($post->signature_is_verified);
    }

    /**
     * El formulario de Filament debe poder guardar guide_id igual que
     * cualquier otro campo (mismo patrón de Livewire::test ya usado en
     * BlogPostNewMockupFieldsTest para author_photo).
     */
    public function test_admin_can_assign_a_real_guide_from_the_filament_form(): void
    {
        $guide = Guide::factory()->create(['name' => 'Samira']);
        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title_es' => 'QA_ Artículo con guía real',
                'excerpt_es' => 'Extracto de prueba.',
                'body_es' => 'Cuerpo de prueba.',
                'guide_id' => $guide->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::where('title_es', 'QA_ Artículo con guía real')->firstOrFail();

        $this->assertSame($guide->id, $post->guide_id);
        $this->assertSame('Samira', $post->signature_name);
    }

    // ────────────────────────────────────────────────────────────────
    // video_url → video_embed_url (§1, botón de play del hero)
    // ────────────────────────────────────────────────────────────────

    public function test_video_embed_url_normalizes_a_pasted_youtube_share_link(): void
    {
        $post = $this->makePost(['video_url' => 'https://www.youtube.com/watch?v=abc123XYZ_9']);

        $this->assertSame('https://www.youtube-nocookie.com/embed/abc123XYZ_9', $post->video_embed_url);
    }

    /**
     * Sin URL, el botón de play debe poder ocultarse — el accesor no debe
     * devolver una cadena vacía ni una URL rota.
     */
    public function test_video_embed_url_is_null_when_there_is_no_video_url(): void
    {
        $this->assertNull($this->makePost()->video_embed_url);
    }

    public function test_video_embed_url_is_null_for_an_unrecognized_url(): void
    {
        $post = $this->makePost(['video_url' => 'https://example.com/not-a-video']);

        $this->assertNull($post->video_embed_url);
    }

    // ────────────────────────────────────────────────────────────────
    // quote_text_* / quote_attribution (cita destacada)
    // ────────────────────────────────────────────────────────────────

    public function test_quote_text_resolves_the_active_locale(): void
    {
        app()->setLocale('en');

        $post = $this->makePost([
            'quote_text_es' => 'Cita en español',
            'quote_text_en' => 'Quote in English',
        ]);

        $this->assertSame('Quote in English', $post->quote_text);
    }

    public function test_quote_text_falls_back_to_spanish_when_the_active_locale_is_empty(): void
    {
        app()->setLocale('pt');

        $post = $this->makePost(['quote_text_es' => 'Cita en español']);

        $this->assertSame('Cita en español', $post->quote_text);
    }

    /**
     * Sin ninguna cita cargada en ningún idioma, el accesor devuelve null
     * (no ''), para que el componente de la cita destacada pueda ocultarse
     * con un simple @if en vez de imprimir un <blockquote> vacío.
     */
    public function test_quote_text_is_null_when_nothing_was_written(): void
    {
        $this->assertNull($this->makePost()->quote_text);
    }

    public function test_quote_attribution_is_null_when_blank(): void
    {
        $this->assertNull($this->makePost()->quote_attribution);
    }

    public function test_quote_attribution_persists_and_reads_back(): void
    {
        $post = $this->makePost(['quote_attribution' => '— Augusto, Guía Local']);

        $this->assertSame('— Augusto, Guía Local', $post->fresh()->quote_attribution);
    }
}
