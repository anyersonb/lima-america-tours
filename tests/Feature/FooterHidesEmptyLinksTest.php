<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Enlaces del footer que llevaban a una pantalla vacía.
 *
 * "Free Tours" no es una sección: es la búsqueda `?q=free`. Con el catálogo
 * actual devuelve 0 resultados, así que quien hacía clic recibía un "Sin
 * resultados" en vez de una sección — un enlace muerto en el footer de todas
 * las páginas. Igual el Blog si algún día no queda ningún post publicado.
 *
 * Se resuelve por CONDICIÓN, no comentando el enlace: el día que el CMS tenga
 * free tours o posts, el enlace vuelve solo, sin que nadie tenga que acordarse.
 *
 * Antes del fix: falla (los dos enlaces se pintan siempre).
 * Después del fix: pasa.
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

    public function test_free_tours_link_is_hidden_when_no_tour_matches(): void
    {
        Tour::factory()->create(['title_es' => 'City Tour Centro Histórico', 'is_published' => true]);

        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        $this->assertStringNotContainsString(
            'buscar?q=free',
            $footer,
            'El footer enlaza Free Tours sin que exista ningún tour free: lleva a "Sin resultados".'
        );
    }

    public function test_free_tours_link_comes_back_when_there_is_a_free_tour(): void
    {
        Tour::factory()->create(['title_es' => 'Free Walking Tour por Miraflores', 'is_published' => true]);

        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        $this->assertStringContainsString(
            'buscar?q=free',
            $footer,
            'Con un tour free publicado, el enlace del footer debe reaparecer solo.'
        );
    }

    public function test_blog_link_is_hidden_when_there_are_no_published_posts(): void
    {
        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        $this->assertStringNotContainsString('/es/blog', $footer, 'El footer enlaza el Blog sin ningún post publicado.');
    }

    public function test_blog_link_is_shown_when_a_post_is_published(): void
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

        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        $this->assertStringContainsString('/es/blog', $footer);
    }

    /** Los enlaces que SIEMPRE tienen contenido detrás no se tocan. */
    public function test_static_links_are_untouched(): void
    {
        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        foreach (['/es/nosotros', '/es/tours', '/es/contacto', '/es/terminos', '/es/privacidad'] as $url) {
            $this->assertStringContainsString($url, $footer, "Desapareció del footer un enlace que sí tiene contenido: {$url}");
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
