<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote del 2026-08-24 — los tres defectos visuales que reportó el jefe por
 * WhatsApp el 2026-08-21:
 *
 *  - "En la parte de servicios me envía a galería": el ancla #servicios estaba
 *    en la tira de garantías, que mide 141 px visibles tras el salto, así que
 *    el 68% de la pantalla que quedaba era la Galería (medido a 1024×700).
 *    Ahora vive en "Explora por categoría".
 *  - "Las líneas": el geoglifo de Nazca en SVG sobre la foto del bloque de
 *    cierre, que a esa opacidad se lee como rayones de pantalla.
 *  - "La línea roja": los dos filetes rojos de 4 px (Galería y footer). Esos
 *    dos viven en el CSS compilado, no en el HTML, así que no se testean acá:
 *    quedaron verificados en el navegador (`::before` sin `content` y
 *    `border-top-width: 0px`).
 */
class HomeServicesAnchorAndLinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_ancla_servicios_apunta_a_explora_por_categoria(): void
    {
        // La sección solo lista categorías con al menos un tour publicado
        // detrás (Category::scopeWithPublishedTours), así que la categoría
        // sola no basta para que la sección exista.
        $cat = Category::create(['slug' => 'aventura', 'name_es' => 'Tours de Aventura', 'is_active' => true, 'order' => 1]);
        Tour::factory()->create(['category_id' => $cat->id, 'is_published' => true]);

        $html = $this->get('/es')->assertOk()->getContent();

        // El id vive en la <section> cuyo encabezado es cats-title.
        $this->assertMatchesRegularExpression(
            '#<section[^>]*id="servicios"[^>]*aria-labelledby="cats-title"#',
            $html,
            'El ancla #servicios ya no está en la sección de categorías.'
        );

        // Y NO en la tira de garantías, que es de donde se movió.
        $this->assertDoesNotMatchRegularExpression(
            '#class="lat-guarantee"[^>]*id="servicios"#',
            $html,
            'El ancla volvió a la tira de garantías: el salto aterriza otra vez sobre la Galería.'
        );
    }

    public function test_sin_categorias_publicadas_el_ancla_no_queda_huerfana(): void
    {
        // La sección de categorías vive dentro de un @if: sin categorías, el
        // ítem "Servicios" del menú apuntaría a un id que no existe y el clic
        // no haría nada. Guard de respaldo en la tira de garantías.
        $this->assertSame(0, Category::count());

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('id="servicios"', $html,
            'Sin categorías no quedó ningún destino para el ítem "Servicios" del menú.');
    }

    public function test_el_geoglifo_de_nazca_ya_no_se_pinta(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString('lat-closing__mark', $html);
        // Una de las rectas del geoglifo, por si el div vuelve con otra clase.
        $this->assertStringNotContainsString('M296 128 L-30 236', $html);
    }
}
