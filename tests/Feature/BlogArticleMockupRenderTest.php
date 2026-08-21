<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Guide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Maquetación del artículo de blog según el mockup de agosto 2026
 * (docs/rebrand/inventario/spec-03-blog.md, deltas §11).
 *
 * Lo que cubre esta clase es lo que la maqueta PUBLICA, no lo que el modelo
 * resuelve — eso ya está en BlogPostSpec03BlogFieldsTest. Cada bloque nuevo
 * del mockup (tarjeta de features, cita, botón de video, firma con avatar y
 * verificado, badge de categoría en los relacionados) depende de un campo
 * que hoy está vacío en los 10 posts publicados, así que todos se prueban en
 * los dos estados: con dato y sin dato. Un bloque que sin dato imprime un
 * placeholder, una foto de stock o una cifra inventada es un defecto, no un
 * pendiente de configuración.
 */
class BlogArticleMockupRenderTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $attributes = []): BlogPost
    {
        return BlogPost::create($attributes + [
            'title_es' => 'QA_ Artículo de maqueta',
            'excerpt_es' => 'QA_BAJADA visible bajo el titular.',
            'body_es' => '<p>QA_CUERPO del artículo.</p>',
            'slug' => 'qa-articulo-de-maqueta',
            'category' => 'Gastronomía',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'reading_minutes' => 6,
        ]);
    }

    private function get_article(BlogPost $post, string $locale = 'es')
    {
        return $this->get(route('blog.show', ['locale' => $locale, 'slug' => $post->slug]));
    }

    // ────────────────────────────────────────────────────────────────
    // Hero: bajada visible y firma
    // ────────────────────────────────────────────────────────────────

    /**
     * La bajada ya existía en BD y solo alimentaba la <meta description>: el
     * mockup la pide visible bajo el H1 (delta §11 #8).
     */
    public function test_the_hero_prints_the_excerpt_on_screen(): void
    {
        $post = $this->makePost();

        $this->get_article($post)
            ->assertOk()
            ->assertSee('lat-post-lead', false)
            ->assertSee('QA_BAJADA visible bajo el titular.');
    }

    /**
     * Sin autor cargado no se inventa uno ni se reserva el hueco del avatar:
     * firma el nombre del sitio y no hay ni <img> de avatar ni check.
     */
    public function test_without_an_author_there_is_no_avatar_and_no_verified_check(): void
    {
        $post = $this->makePost();

        $html = $this->get_article($post)->assertOk()->getContent();

        $this->assertStringNotContainsString('lat-post-sign__avatar', $html);
        $this->assertStringNotContainsString('lat-post-sign__check', $html);
        $this->assertStringContainsString('Lima América Tours', $html);
    }

    /**
     * Con un guía real asignado manda el guía (nombre, rol y foto) y aparece
     * el check de verificado — que sale de tener un Guide, no de un booleano.
     */
    public function test_a_linked_guide_prints_avatar_role_and_verified_check(): void
    {
        $guide = Guide::create([
            'name' => 'QA_Guía Real',
            'role_es' => 'QA_Guía Local',
            'photo' => 'guides/qa-foto.jpg',
            'is_active' => true,
        ]);

        $post = $this->makePost([
            'guide_id' => $guide->id,
            'author_name' => 'QA_NOMBRE_SUELTO',
            'author_role' => 'QA_ROL_SUELTO',
        ]);

        $html = $this->get_article($post)->assertOk()->getContent();

        $this->assertStringContainsString('QA_Guía Real', $html);
        $this->assertStringContainsString('QA_Guía Local', $html);
        $this->assertStringContainsString('lat-post-sign__avatar', $html);
        $this->assertStringContainsString('lat-post-sign__check', $html);
        // Los campos sueltos son respaldo: con guía asignado no se mezclan.
        $this->assertStringNotContainsString('QA_NOMBRE_SUELTO', $html);
        $this->assertStringNotContainsString('QA_ROL_SUELTO', $html);
    }

    // ────────────────────────────────────────────────────────────────
    // Tarjeta de features sobre el hero
    // ────────────────────────────────────────────────────────────────

    public function test_the_features_card_is_absent_when_the_post_has_no_features(): void
    {
        $post = $this->makePost();

        $this->get_article($post)
            ->assertOk()
            ->assertDontSee('lat-post-features', false);
    }

    /**
     * Con dato se imprime la tarjeta, y `data-count` lleva la cantidad REAL:
     * de ese atributo salen las columnas del grid, así que con 2 bloques la
     * tarjeta no puede quedar con dos huecos vacíos.
     */
    public function test_the_features_card_prints_only_the_rows_actually_filled(): void
    {
        $post = $this->makePost([
            'features' => [
                ['icon' => 'heart', 'title_es' => 'QA_Feature uno', 'text_es' => 'QA_Texto uno'],
                ['icon' => 'star', 'title_es' => 'QA_Feature dos', 'text_es' => 'QA_Texto dos'],
                ['icon' => 'pin', 'title_es' => '', 'text_es' => 'fila sin título, se descarta'],
            ],
        ]);

        $html = $this->get_article($post)->assertOk()->getContent();

        $this->assertStringContainsString('data-count="2"', $html);
        $this->assertStringContainsString('QA_Feature uno', $html);
        $this->assertStringContainsString('QA_Feature dos', $html);
        $this->assertStringNotContainsString('fila sin título, se descarta', $html);
        $this->assertSame(2, substr_count($html, 'lat-post-features__item'));
    }

    // ────────────────────────────────────────────────────────────────
    // Cita destacada y botón de video
    // ────────────────────────────────────────────────────────────────

    public function test_the_pull_quote_only_appears_with_a_quote_loaded(): void
    {
        $sinCita = $this->makePost();
        $this->get_article($sinCita)->assertOk()->assertDontSee('lat-post-quote', false);

        $conCita = $this->makePost([
            'slug' => 'qa-articulo-con-cita',
            'quote_text_es' => 'QA_CITA destacada del artículo.',
            'quote_attribution' => '— QA_Autor, Guía Local',
        ]);

        $this->get_article($conCita)
            ->assertOk()
            ->assertSee('lat-post-quote', false)
            ->assertSee('QA_CITA destacada del artículo.')
            // La atribución va en su propia línea, como <cite>, no pegada al
            // cuerpo de la cita (spec §5.2).
            ->assertSee('<cite>— QA_Autor, Guía Local</cite>', false);
    }

    /**
     * Sin URL de video no se imprime ni el botón ni el modal: nada de un
     * botón que abre un iframe vacío.
     */
    public function test_the_play_button_and_its_modal_need_a_video_url(): void
    {
        $post = $this->makePost();
        $html = $this->get_article($post)->assertOk()->getContent();
        $this->assertStringNotContainsString('postVideoBtn', $html);
        $this->assertStringNotContainsString('postVideoModal', $html);

        $conVideo = $this->makePost([
            'slug' => 'qa-articulo-con-video',
            'video_url' => 'https://www.youtube.com/watch?v=abc12345678',
        ]);
        $html = $this->get_article($conVideo)->assertOk()->getContent();
        $this->assertStringContainsString('postVideoBtn', $html);
        $this->assertStringContainsString('postVideoModal', $html);
        // La URL se guarda como enlace de compartir y se publica ya
        // normalizada a la forma incrustable.
        $this->assertStringContainsString('youtube-nocookie.com/embed/abc12345678', $html);
    }

    // ────────────────────────────────────────────────────────────────
    // Breadcrumb de 4 niveles
    // ────────────────────────────────────────────────────────────────

    public function test_the_breadcrumb_adds_the_category_level_only_when_there_is_a_category(): void
    {
        $conCategoria = $this->makePost();
        $html = $this->get_article($conCategoria)->assertOk()->getContent();
        // Inicio › Blog › Categoría › título = 3 separadores
        $this->assertSame(3, substr_count($html, 'lat-crumb__sep'));
        $this->assertStringContainsString('"position": 4', $html);

        $sinCategoria = $this->makePost(['slug' => 'qa-sin-categoria', 'category' => null]);
        $html = $this->get_article($sinCategoria)->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'lat-crumb__sep'));
        $this->assertStringContainsString('"position": 3', $html);
        $this->assertStringNotContainsString('"position": 4', $html);
    }

    // ────────────────────────────────────────────────────────────────
    // Sidebar: ninguna cifra inventada
    // ────────────────────────────────────────────────────────────────

    /**
     * El mockup del sidebar dice "+2.500 viajeros ya lo vivieron". Esa cifra
     * no existe en ninguna tabla del proyecto y no se publica: sin reseñas
     * cargadas, el sidebar sale sin ninguna prueba social.
     */
    public function test_the_sidebar_never_publishes_the_mockups_invented_figure(): void
    {
        $post = $this->makePost();

        $html = $this->get_article($post)->assertOk()->getContent();

        $this->assertStringContainsString('lat-post-cta', $html);
        $this->assertStringNotContainsString('2.500', $html);
        $this->assertStringNotContainsString('2,500', $html);
        // Sin testimonios activos no hay agregado real que mostrar.
        $this->assertStringNotContainsString('lat-post-cta__proof', $html);
    }

    // ────────────────────────────────────────────────────────────────
    // Artículos relacionados
    // ────────────────────────────────────────────────────────────────

    /**
     * Delta §11 #10: a esta variante de tarjeta le faltaban el badge de
     * categoría encimado y los minutos de lectura que sí tiene la del
     * listado.
     */
    public function test_related_cards_print_the_category_badge_and_the_reading_time(): void
    {
        $post = $this->makePost();
        BlogPost::create([
            'title_es' => 'QA_ Relacionado',
            'excerpt_es' => 'x',
            'body_es' => 'x',
            'slug' => 'qa-relacionado',
            'category' => 'QA_CategoriaRelacionada',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'reading_minutes' => 9,
        ]);

        $html = $this->get_article($post)->assertOk()->getContent();

        $this->assertStringContainsString('lat-related-card__badge', $html);
        $this->assertStringContainsString('QA_CategoriaRelacionada', $html);
        $this->assertStringContainsString('lat-related-card__meta', $html);
        $this->assertStringContainsString('9 min', $html);
    }
}
