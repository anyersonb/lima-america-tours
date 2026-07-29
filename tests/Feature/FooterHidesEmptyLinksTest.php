<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bloque "Enlaces" del footer (`<nav aria-labelledby="footer-links">`).
 *
 * Decisión del jefe (2026-07-29): ese bloque lista SOLO Inicio · Nosotros ·
 * Tours, igual que el menú de cabecera. Los demás enlaces quedan comentados en
 * la vista, no borrados: rutas y vistas siguen vivas y accesibles por URL.
 *
 * Términos y Política de privacidad NO desaparecen del footer: viven en la
 * barra inferior (`.lat-footer__legal`), que es lo que exige la pasarela de
 * pago y espera Google. Este test lo fija para que un futuro "limpiemos el
 * footer" no se los lleve por delante.
 *
 * Antes vivía aquí el fix de "Free Tours"/"Blog" condicionales (enlaces que
 * llevaban a "Sin resultados"). Ahora esos enlaces no se pintan nunca, así que
 * los tests comprueban lo más fuerte: que no aparezcan ni con contenido detrás.
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

    public function test_links_block_lists_exactly_home_about_and_tours(): void
    {
        Tour::factory()->create(['title_es' => 'City Tour Centro Histórico', 'is_published' => true]);

        $nav = $this->linksNav($this->get('/es')->assertOk()->getContent());

        preg_match_all('/href="([^"]*)"/', $nav, $m);

        $this->assertSame(
            ['/es', '/es/nosotros', '/es/tours'],
            array_map(fn ($h) => parse_url($h, PHP_URL_PATH), $m[1]),
            'El bloque "Enlaces" del footer debe listar solo Inicio, Nosotros y Tours.'
        );
    }

    public function test_free_tours_link_is_not_shown_even_with_a_free_tour(): void
    {
        Tour::factory()->create(['title_es' => 'Free Walking Tour por Miraflores', 'is_published' => true]);

        $nav = $this->linksNav($this->get('/es')->assertOk()->getContent());

        $this->assertStringNotContainsString('q=free', $nav);
    }

    public function test_blog_link_is_not_shown_even_with_a_published_post(): void
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

        $nav = $this->linksNav($this->get('/es')->assertOk()->getContent());

        $this->assertStringNotContainsString('/es/blog', $nav);
    }

    /** Legales: fuera del bloque de enlaces, pero siguen en la barra inferior. */
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
