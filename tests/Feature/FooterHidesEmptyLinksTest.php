<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Navegación completa: bloque "Enlaces" del footer (`<nav
 * aria-labelledby="footer-links">`) y menú de cabecera (`.lat-nav-links`).
 *
 * Decisión de Anyerson (2026-08-03): se reactivan todos los botones del menú y
 * el footer lo espeja. Deroga la reducción a Inicio · Nosotros · Tours del
 * 2026-07-29, que es lo que fijaba la versión anterior de este archivo.
 *
 * Lo que NO cambió es el criterio de fondo, y es lo que estos tests protegen:
 * un enlace de navegación no puede llevar a una sección vacía. Por eso
 * "Free Tours" (que no es una sección, es la búsqueda ?q=free) y "Blog"
 * dependen de que exista contenido detrás — guards `$hasFreeTours` y
 * `$hasBlogPosts` en header.blade.php y footer.blade.php.
 *
 * Términos y Política de privacidad aparecen ahora en el bloque de enlaces Y
 * siguen en la barra inferior (`.lat-footer__legal`), que es lo que exige la
 * pasarela de pago y espera Google.
 */
class FooterHidesEmptyLinksTest extends TestCase
{
    use RefreshDatabase;

    private function footer(string $html): string
    {
        $start = strpos($html, '<footer');
        $this->assertNotFalse($start, 'No se encontró el footer.');

        return substr($html, $start);
    }

    /** Solo el bloque "Enlaces", sin las otras columnas del footer. */
    private function linksNav(string $html): string
    {
        $footer = $this->footer($html);

        $start = strpos($footer, '<nav aria-labelledby="footer-links"');
        $this->assertNotFalse($start, 'No se encontró el bloque de enlaces del footer.');

        $end = strpos($footer, '</nav>', $start);
        $this->assertNotFalse($end, 'El bloque de enlaces del footer no cierra.');

        return substr($footer, $start, $end - $start);
    }

    /** El menú de cabecera, que comparte lista con el drawer de móvil. */
    private function headerNav(string $html): string
    {
        $start = strpos($html, '<div class="lat-nav-links">');
        $this->assertNotFalse($start, 'No se encontró el menú de cabecera.');

        $end = strpos($html, '</div>', $start);
        $this->assertNotFalse($end, 'El menú de cabecera no cierra.');

        return substr($html, $start, $end - $start);
    }

    /** @return string[] Rutas (sin dominio ni query) de los href del fragmento. */
    private function paths(string $fragment): array
    {
        preg_match_all('/href="([^"]*)"/', $fragment, $m);

        return array_map(fn ($h) => parse_url($h, PHP_URL_PATH), $m[1]);
    }

    public function test_links_block_lists_the_full_menu(): void
    {
        Tour::factory()->create(['title_es' => 'City Tour Centro Histórico', 'is_published' => true]);

        $nav = $this->linksNav($this->get('/es')->assertOk()->getContent());

        // Sin tours "free" ni entradas de blog: esos dos no deben estar.
        $this->assertSame(
            ['/es', '/es/nosotros', '/es/tours', '/es/contacto', '/es/terminos', '/es/privacidad'],
            $this->paths($nav),
            'El bloque "Enlaces" del footer no lista el menú completo.'
        );
    }

    public function test_header_menu_lists_the_full_menu(): void
    {
        Tour::factory()->create(['title_es' => 'City Tour Centro Histórico', 'is_published' => true]);

        $nav = $this->headerNav($this->get('/es')->assertOk()->getContent());

        // "Servicios" es un ancla al bloque de garantías del home, no una ruta:
        // por eso su path es "/es" igual que Inicio.
        //
        // Los cuatro últimos se agregaron en el lote de agosto 2026, cuando el jefe
        // pidió el patrón de menú de limaviewtours.com: sus rutas ya existían, lo que
        // faltaba era ofrecerlas. Van al final a propósito, para no reordenar el nav
        // de escritorio (docs/rebrand/inventario/01-nosotros-y-menu.md).
        $this->assertSame(
            [
                '/es', '/es/nosotros', '/es/tours', '/es', '/es/blog', '/es/contacto',
                '/es/mi-cuenta', '/es/carrito', '/es/resenas', '/es/ingresar',
            ],
            $this->paths($nav),
            'El menú de cabecera no lista los 10 ítems: Inicio, Nosotros, Tours, Servicios, Blog, Contacto, Mis reservas, Carrito, Reseñas e Ingresar.'
        );
    }

    public function test_free_tours_link_appears_when_a_free_tour_exists(): void
    {
        Tour::factory()->create(['title_es' => 'Free Walking Tour por Miraflores', 'is_published' => true]);

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('q=free', $this->headerNav($html),
            'Con un tour "free" publicado, el menú debe ofrecer Free Tours.');
        $this->assertStringContainsString('q=free', $this->linksNav($html),
            'Con un tour "free" publicado, el footer debe ofrecer Free Tours.');
    }

    public function test_free_tours_link_is_hidden_without_free_tours(): void
    {
        Tour::factory()->create(['title_es' => 'City Tour Centro Histórico', 'is_published' => true]);

        $html = $this->get('/es')->assertOk()->getContent();

        // Sin tours "free", ?q=free devuelve 0 resultados: enlazarlo es mandar
        // al visitante a una página vacía.
        $this->assertStringNotContainsString('q=free', $this->headerNav($html));
        $this->assertStringNotContainsString('q=free', $this->linksNav($html));
    }

    public function test_blog_link_appears_when_a_post_is_published(): void
    {
        // Las columnas de contenido son NOT NULL (excerpt_es / body_es): el
        // modelo se llama así, no `content_es`.
        BlogPost::create([
            'slug' => 'ceviche-peruano',
            'title_es' => 'Cómo se prepara el ceviche',
            'excerpt_es' => 'Receta paso a paso del ceviche peruano.',
            'body_es' => 'Contenido de prueba suficientemente largo para el listado del blog.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->assertStringContainsString(
            '/es/blog',
            $this->linksNav($this->get('/es')->assertOk()->getContent()),
            'Con una entrada publicada, el footer debe enlazar el blog.'
        );
    }

    public function test_blog_link_is_hidden_from_the_footer_without_posts(): void
    {
        $this->assertStringNotContainsString(
            '/es/blog',
            $this->linksNav($this->get('/es')->assertOk()->getContent()),
            'Sin entradas publicadas, el footer no debe enlazar un listado vacío.'
        );
    }

    /** Legales: en el bloque de enlaces y también en la barra inferior. */
    public function test_legal_links_stay_in_the_bottom_bar(): void
    {
        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        foreach (['/es/terminos', '/es/privacidad'] as $url) {
            $this->assertStringContainsString($url, $footer, "Desapareció del footer un enlace legal obligatorio: {$url}");
        }
    }

    /** Ningún enlace del footer puede quedar sin destino (href vacío o "#"). */
    public function test_no_footer_link_points_nowhere(): void
    {
        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        preg_match_all('/href="(#?)"/', $footer, $m);

        $this->assertSame([], $m[0], 'Hay enlaces del footer con href vacío o "#": no hacen nada al pulsarlos.');
    }
}
