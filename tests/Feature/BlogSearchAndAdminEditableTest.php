<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tarea D del lote 2026-08-10: buscador + filtro por categoría en /blog
 * (server-side), y 3 gaps de administrabilidad en BlogPostResource:
 * minutos de lectura editable de verdad, autor sin imprimir vacío, portada
 * con placeholder cuando el archivo falta.
 */
class BlogSearchAndAdminEditableTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'slug' => 'post-'.uniqid(),
            'title_es' => 'Título de prueba',
            'excerpt_es' => 'Extracto de prueba.',
            'body_es' => 'Cuerpo de prueba suficientemente largo para el listado del blog.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    // ── Buscador server-side ────────────────────────────────────────────

    public function test_search_by_title_returns_only_matching_published_posts(): void
    {
        $this->makePost(['title_es' => 'Cómo se prepara el ceviche peruano']);
        $this->makePost(['title_es' => 'Full Day Ica - Huacachina']);

        $response = $this->get('/es/blog?q=ceviche');

        $response->assertOk();
        $response->assertViewHas('posts', function ($posts) {
            return $posts->total() === 1
                && str_contains($posts->first()->title_es, 'ceviche');
        });
    }

    public function test_search_with_no_matches_returns_zero_results_not_an_error(): void
    {
        $this->makePost(['title_es' => 'Cómo se prepara el ceviche peruano']);

        $response = $this->get('/es/blog?q=palabra-que-no-existe-en-ningun-post');

        $response->assertOk();
        $response->assertViewHas('posts', fn ($posts) => $posts->total() === 0);
    }

    public function test_search_ignores_unpublished_posts(): void
    {
        $this->makePost(['title_es' => 'Ceviche borrador', 'is_published' => false]);

        $response = $this->get('/es/blog?q=ceviche');

        $response->assertOk();
        $response->assertViewHas('posts', fn ($posts) => $posts->total() === 0);
    }

    public function test_category_pills_only_list_categories_present_on_published_posts(): void
    {
        $this->makePost(['category' => 'Gastronomía']);
        $this->makePost(['category' => 'Solo en borrador', 'is_published' => false]);
        $this->makePost(['category' => null]);

        $response = $this->get('/es/blog');

        $response->assertOk();
        $response->assertViewHas('categories', function ($categories) {
            return $categories->count() === 1 && $categories->first() === 'Gastronomía';
        });
    }

    public function test_categoria_and_q_filters_combine(): void
    {
        $this->makePost(['title_es' => 'Ceviche en Barranco', 'category' => 'Gastronomía']);
        $this->makePost(['title_es' => 'Ceviche a domicilio', 'category' => 'Consejos']);

        $response = $this->get('/es/blog?q=ceviche&categoria=Gastronomía');

        $response->assertOk();
        $response->assertViewHas('posts', function ($posts) {
            return $posts->total() === 1 && $posts->first()->category === 'Gastronomía';
        });
    }

    // ── Minutos de lectura: editable, no se pisa con el auto-cálculo ─────

    public function test_reading_minutes_is_auto_calculated_when_left_empty(): void
    {
        $post = $this->makePost(['body_es' => str_repeat('palabra ', 400), 'reading_minutes' => null]);

        $this->assertSame(2, $post->reading_minutes); // 400 palabras / 200 = 2
    }

    public function test_manual_reading_minutes_is_not_overwritten_on_save(): void
    {
        $post = $this->makePost(['body_es' => str_repeat('palabra ', 400), 'reading_minutes' => 12]);

        $this->assertSame(12, $post->reading_minutes);

        // Guardar de nuevo (ej. el editor corrige el título) no debe pisar
        // el valor manual con el auto-cálculo.
        $post->title_es = 'Título editado';
        $post->save();
        $post->refresh();

        $this->assertSame(12, $post->reading_minutes);
    }

    // ── Autor: nunca una línea vacía ─────────────────────────────────────

    public function test_author_name_returns_the_stored_value_when_present(): void
    {
        $post = $this->makePost(['author_name' => 'Lima América Tours']);
        $this->assertSame('Lima América Tours', $post->author_name);
    }

    public function test_author_name_falls_back_to_site_name_setting_when_blank(): void
    {
        Setting::set('site_name', 'Lima América Tours');
        $post = $this->makePost(['author_name' => null]);

        $this->assertSame('Lima América Tours', $post->author_name);
    }

    public function test_author_name_is_null_when_blank_and_no_site_name_configured(): void
    {
        $post = $this->makePost(['author_name' => null]);
        $this->assertNull($post->author_name);
    }

    // ── Portada: placeholder cuando el archivo falta o no hay ninguno ────

    public function test_cover_url_falls_back_to_the_generic_banner_when_no_cover_is_set(): void
    {
        $post = $this->makePost(['cover_image' => null]);

        $this->assertStringContainsString('banner-hero.jpg', $post->cover_url);
    }

    /**
     * El caso real que motivó esta tarea: la fila trae una RUTA de portada,
     * pero el archivo no existe en el disco (staging con la BD copiada y el
     * upload sin sincronizar) — antes eso renderizaba un <img> roto ("un
     * rectángulo gris"). Ahora cae al mismo placeholder genérico.
     */
    public function test_cover_url_falls_back_when_the_referenced_file_does_not_exist_on_disk(): void
    {
        Storage::fake('public');
        $post = $this->makePost(['cover_image' => 'blog/no-existe-en-disco.jpg']);

        $this->assertStringContainsString('banner-hero.jpg', $post->cover_url);
    }

    public function test_cover_url_uses_the_real_file_when_it_exists_on_disk(): void
    {
        Storage::fake('public');
        // Contenido literal, no UploadedFile::fake()->image(): ese helper no
        // garantiza bytes reales en el archivo final (ver
        // feedback_uploadedfile_fake_vacio) — lo único que importa aquí es
        // que Storage::exists() encuentre el archivo.
        Storage::disk('public')->put('blog/real.jpg', 'contenido-de-prueba');

        $post = $this->makePost(['cover_image' => 'blog/real.jpg']);

        $this->assertStringNotContainsString('banner-hero.jpg', $post->cover_url);
        $this->assertStringContainsString('blog/real.jpg', $post->cover_url);
    }
}
