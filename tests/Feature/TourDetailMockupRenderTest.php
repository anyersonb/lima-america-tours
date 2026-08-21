<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Testimonial;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha de tour reestructurada según el mockup de agosto 2026
 * (docs/rebrand/inventario/spec-02-tour.md).
 *
 * El cambio no es un reskin, es un reordenamiento: el título y el rating se
 * encimaron sobre la foto, la barra de 5 datos se pegó al borde inferior de
 * la foto, Descripción e Incluye salieron del sistema de tabs y quedaron
 * siempre visibles a dos columnas, y los otros tres paneles bajaron a un
 * acordeón (decisión de Anyerson del 2026-08-21).
 *
 * Como en el resto del proyecto, cada pieza que depende de un campo del CMS
 * se prueba con dato y sin dato: sin video no hay botón de play, sin imagen
 * de mapa no hay caja de mapa y sin reseñas reales no hay reseña destacada.
 */
class TourDetailMockupRenderTest extends TestCase
{
    use RefreshDatabase;

    private function makeTour(array $attributes = []): Tour
    {
        return Tour::factory()->create($attributes + [
            'slug' => 'qa-ficha-mockup',
            'title_es' => 'QA_ Tour de maqueta',
            'description_es' => "QA_DESCRIPCION primer párrafo.\nQA_DESCRIPCION segundo párrafo.",
            'includes_es' => ['QA_INCLUYE uno', 'QA_INCLUYE dos'],
            'excludes_es' => ['QA_EXCLUYE uno'],
            'itinerary_es' => [
                ['title' => 'QA_PASO uno', 'description' => 'x'],
                ['title' => 'QA_PASO dos', 'description' => 'x'],
                ['title' => 'QA_PASO tres', 'description' => 'x'],
                ['title' => 'QA_PASO cuatro', 'description' => 'x'],
                ['title' => 'QA_PASO cinco (no va en destacados)', 'description' => 'x'],
            ],
            'recommendations_es' => "QA_LLEVAR uno",
            'notes_es' => "QA_NOTA uno",
            'duration' => '4 Horas',
            'group_type' => 'Grupal / Privado',
            'language' => 'Español / Inglés',
            'is_published' => true,
            'reviews_count' => 0,
        ]);
    }

    private function get_ficha(Tour $tour, string $locale = 'es')
    {
        return $this->get(route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]));
    }

    // ────────────────────────────────────────────────────────────────
    // Hero: título encimado, barra oscura, ribbon, video
    // ────────────────────────────────────────────────────────────────

    /**
     * El título vive DENTRO del hero (encimado sobre la foto) y el eyebrow de
     * categoría que estaba arriba del H1 ya no existe: el mockup no lo tiene y
     * el dato pasó al breadcrumb.
     */
    public function test_the_title_is_inside_the_hero_and_the_old_eyebrow_is_gone(): void
    {
        $tour = $this->makeTour();

        $html = $this->get_ficha($tour)->assertOk()->getContent();

        $this->assertStringContainsString('lat-tour-hero__overlay', $html);
        $this->assertStringContainsString('lat-detail-info--dark', $html);
        $this->assertStringNotContainsString('lat-detail-eyebrow', $html);

        // El H1 aparece después de la apertura del hero, no antes.
        $this->assertGreaterThan(
            strpos($html, 'lat-tour-hero'),
            strpos($html, 'lat-detail-title'),
            'El H1 debería quedar dentro del hero, no arriba de la galería.'
        );
    }

    /**
     * La categoría no se pierde al sacar el eyebrow: entra como nivel del
     * breadcrumb, con su enlace real.
     */
    public function test_the_category_moves_into_the_breadcrumb(): void
    {
        $categoria = Category::create(['name_es' => 'QA_Categoría', 'slug' => 'qa-categoria']);
        $tour = $this->makeTour(['category_id' => $categoria->id]);

        $this->get_ficha($tour)
            ->assertOk()
            ->assertSee('QA_Categoría')
            ->assertSee('tours/categoria/qa-categoria', false);
    }

    public function test_the_ribbon_needs_a_badge_and_the_play_button_needs_a_video(): void
    {
        $pelado = $this->makeTour();
        $html = $this->get_ficha($pelado)->assertOk()->getContent();
        $this->assertStringNotContainsString('lat-tour-hero__ribbon', $html);
        $this->assertStringNotContainsString('tourVideoBtn', $html);
        $this->assertStringNotContainsString('tourVideoModal', $html);

        $completo = $this->makeTour([
            'slug' => 'qa-ficha-con-badge',
            'badge_text' => 'QA_Más vendido',
            'video_url' => 'https://youtu.be/abc12345678',
        ]);
        $html = $this->get_ficha($completo)->assertOk()->getContent();
        $this->assertStringContainsString('lat-tour-hero__ribbon', $html);
        $this->assertStringContainsString('QA_Más vendido', $html);
        $this->assertStringContainsString('tourVideoBtn', $html);
        $this->assertStringContainsString('youtube-nocookie.com/embed/abc12345678', $html);
    }

    /**
     * La barra de 5 datos: "Salidas" sale (el mockup no lo pide), entra
     * "Dificultad" y la cancelación deja de ser una pastilla verde suelta al
     * lado del rating. El label del tipo de grupo estaba mal: el valor real es
     * "Grupal / Privado", no un tamaño de grupo.
     */
    public function test_the_dark_bar_carries_the_five_data_points_of_the_mockup(): void
    {
        $tour = $this->makeTour([
            'difficulty' => 'QA_Camina fácil',
            'departure_time' => '09:00',
            'return_time' => '13:00',
        ]);

        $html = $this->get_ficha($tour)->assertOk()->getContent();

        $this->assertStringContainsString('QA_Camina fácil', $html);
        $this->assertStringContainsString('Dificultad', $html);
        $this->assertStringContainsString('Opciones', $html);
        $this->assertStringNotContainsString('Tamaño del grupo', $html);
        $this->assertStringNotContainsString('>Salidas<', $html);
        // La pastilla verde suelta desaparece: la cancelación vive en la barra.
        $this->assertStringNotContainsString('lat-badge-free', $html);
    }

    public function test_the_difficulty_slot_is_absent_when_the_field_is_empty(): void
    {
        $tour = $this->makeTour();

        $this->get_ficha($tour)->assertOk()->assertDontSee('Dificultad');
    }

    // ────────────────────────────────────────────────────────────────
    // Miniaturas: una fila de hasta 8 + casilla "+N"
    // ────────────────────────────────────────────────────────────────

    public function test_the_thumbnail_strip_caps_at_eight_and_the_last_one_counts_the_rest(): void
    {
        $tour = $this->makeTour([
            'gallery' => array_map(fn ($i) => "tours/qa-foto-$i.jpg", range(1, 11)),
        ]);

        $html = $this->get_ficha($tour)->assertOk()->getContent();

        $this->assertSame(8, substr_count($html, 'lat-gal__thumb '), 'La tira debe cortar en 8 casillas.');
        $this->assertStringContainsString('galMoreThumb', $html);
        $this->assertStringContainsString('+3', $html);   // 11 fotos, 8 en la tira
        // La pastilla vieja sobre la foto ya no se imprime.
        $this->assertStringNotContainsString('id="galMoreBtn"', $html);
    }

    // ────────────────────────────────────────────────────────────────
    // Fila de 3 cajas
    // ────────────────────────────────────────────────────────────────

    /**
     * Los destacados son los primeros 4 pasos del itinerario del CMS, no un
     * campo nuevo ni una lista inventada: el quinto paso no aparece.
     */
    public function test_the_highlights_box_takes_the_first_four_itinerary_steps(): void
    {
        $tour = $this->makeTour();

        $html = $this->get_ficha($tour)->assertOk()->getContent();

        $this->assertStringContainsString('lat-tbox__list', $html);
        // Los 4 primeros pasos salen DOS veces: en la caja de destacados y en
        // el panel de itinerario del acordeón. El quinto, solo en el acordeón:
        // así se prueba que la caja corta en 4 sin exigir que el itinerario
        // completo desaparezca de la página.
        $this->assertSame(2, substr_count($html, 'QA_PASO uno'));
        $this->assertSame(2, substr_count($html, 'QA_PASO cuatro'));
        $this->assertSame(1, substr_count($html, 'QA_PASO cinco (no va en destacados)'));
    }

    public function test_the_map_box_needs_an_uploaded_image(): void
    {
        $sinMapa = $this->makeTour();
        $this->get_ficha($sinMapa)->assertOk()->assertDontSee('id="mapLightbox"', false);

        $conMapa = $this->makeTour(['slug' => 'qa-ficha-con-mapa', 'route_map_image' => 'tours/qa-mapa.jpg']);
        $this->get_ficha($conMapa)
            ->assertOk()
            ->assertSee('id="mapLightbox"', false)
            ->assertSee('lat-tbox--map', false);
    }

    public function test_the_featured_review_box_needs_a_real_review(): void
    {
        $tour = $this->makeTour();
        $this->get_ficha($tour)->assertOk()->assertDontSee('lat-tbox--quote', false);

        Testimonial::create([
            'name' => 'QA_Viajera',
            'country' => 'QA_País',
            'quote_es' => 'QA_CITA de la reseña destacada.',
            'rating' => 5,
            'is_active' => true,
            'tour_id' => $tour->id,
        ]);

        $this->get_ficha($tour)
            ->assertOk()
            ->assertSee('lat-tbox--quote', false)
            ->assertSee('QA_CITA de la reseña destacada.')
            ->assertSee('QA_Viajera');
    }

    /**
     * Sin itinerario, sin mapa y sin reseñas, la fila entera desaparece: no
     * quedan tres tarjetas vacías de relleno.
     */
    public function test_the_whole_boxes_row_disappears_without_any_data(): void
    {
        $tour = $this->makeTour(['itinerary_es' => [], 'route_map_image' => null]);

        $this->get_ficha($tour)->assertOk()->assertDontSee('lat-tour-boxes', false);
    }

    // ────────────────────────────────────────────────────────────────
    // Descripción + Incluye fuera de los tabs, y el acordeón que queda
    // ────────────────────────────────────────────────────────────────

    public function test_description_and_includes_are_always_visible_outside_the_tabs(): void
    {
        $tour = $this->makeTour();

        $html = $this->get_ficha($tour)->assertOk()->getContent();

        $this->assertStringContainsString('lat-tour-cols', $html);
        $this->assertStringContainsString('QA_DESCRIPCION primer párrafo.', $html);
        $this->assertStringContainsString('QA_INCLUYE uno', $html);
        $this->assertStringContainsString('QA_EXCLUYE uno', $html);
        // Ya no son paneles del sistema de tabs.
        $this->assertStringNotContainsString('data-tab="about"', $html);
        $this->assertStringNotContainsString('data-tab="incl"', $html);
    }

    /**
     * Los tres paneles que el mockup no maquetó siguen publicados, plegados en
     * un acordeón: su contenido existe en las 24 fichas reales y no se tira.
     */
    public function test_the_three_remaining_panels_stay_as_an_accordion(): void
    {
        $tour = $this->makeTour();

        $html = $this->get_ficha($tour)->assertOk()->getContent();

        $this->assertStringContainsString('lat-tabs-wrap--accordion', $html);
        $this->assertStringContainsString('data-tab="itin"', $html);
        $this->assertStringContainsString('data-tab="bring"', $html);
        $this->assertStringContainsString('data-tab="notes"', $html);
        $this->assertStringContainsString('QA_PASO cinco (no va en destacados)', $html);
        $this->assertStringContainsString('QA_LLEVAR uno', $html);
        $this->assertStringContainsString('QA_NOTA uno', $html);
        // El itinerario arranca abierto para que la sección no sea tres títulos
        // sin contenido.
        $this->assertStringContainsString('aria-expanded="true"', $html);
    }
}
