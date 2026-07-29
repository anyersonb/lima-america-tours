<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El mockup del cliente pone el nombre de marca como titular grande del hero.
 * Tomado literal, el H1 de la home — la página con más autoridad del dominio —
 * pasa a decir solo "Lima América Tours" y pierde el intent transaccional que
 * antes tenía ("Tours en Lima…").
 *
 * Resolución aplicada (cero cambio visual): el <h1> deja de envolver SOLO la
 * marca y pasa a envolver marca + subtitular rojo, que ya son dos elementos
 * VISIBLES y contiguos del mockup, con el copy del cliente intacto. Así el
 * texto del H1 vuelve a contener los términos de cabecera (Lima / Tours /
 * Perú) sin texto oculto, sin cambiar una sola medida del diseño y sin quitarle
 * al cliente su titular de marca.
 *
 * Este test blinda tres cosas a la vez:
 *   - Hay UN solo H1 en la home.
 *   - Ese H1 contiene marca + subtitular (no solo la marca).
 *   - El <title> conserva la keyword exacta.
 *
 * Antes del fix: falla (el H1 solo contiene la marca).
 * Después del fix: pasa.
 */
class HomeHeroH1KeywordTest extends TestCase
{
    use RefreshDatabase;

    private function h1(string $html): string
    {
        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/s', $html, $m);
        $this->assertNotEmpty($m[1] ?? '', 'No se encontró un <h1> en el home.');

        return trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function test_home_has_exactly_one_h1(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1\b/', $html), 'El home debe tener exactamente un H1.');
    }

    public function test_h1_keeps_the_brand_and_the_head_terms(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();
        $h1 = $this->h1($html);

        // La marca que pidió el cliente sigue en el H1.
        $this->assertStringContainsString('Lima', $h1);
        $this->assertStringContainsString('América', $h1);

        // Y vuelven los términos de búsqueda que el titular de marca solo no cubre.
        $this->assertStringContainsString('Tours', $h1);
        $this->assertStringContainsString('Perú', $h1, 'El H1 perdió el término geográfico: '.$h1);
    }

    public function test_h1_wraps_both_the_brand_line_and_the_red_tagline(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/s', $html, $m);
        $inner = $m[1] ?? '';

        $this->assertStringContainsString('lat-hero__brand', $inner, 'La marca debe seguir dentro del H1, con su propia clase visual.');
        $this->assertStringContainsString('lat-hero__tagline', $inner, 'El subtitular rojo debe estar DENTRO del H1 (es lo que devuelve la keyword).');
    }

    public function test_the_tagline_is_no_longer_a_separate_paragraph_outside_the_h1(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            '<p class="lat-hero__tagline"',
            $html,
            'El subtitular quedó duplicado: sigue existiendo como <p> fuera del H1.'
        );
    }

    public function test_title_tag_still_carries_the_exact_keyword(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/s', $html, $m);
        $title = html_entity_decode($m[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertStringContainsString('Tours en Lima', $title, "El <title> perdió la keyword exacta: {$title}");
    }

    public function test_brand_line_is_not_rendered_as_another_heading(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        // La marca no debe quedar como h2/h3 suelto (eso rompería la jerarquía).
        $this->assertDoesNotMatchRegularExpression(
            '/<h[23][^>]*class="[^"]*lat-hero__brand/',
            $html
        );
    }
}
