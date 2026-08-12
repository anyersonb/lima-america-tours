<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jerarquía de encabezados sin saltos, y un solo H1 por página.
 *
 * El gate de regresión SEO bloqueó el lote del 2026-08-12 por dos saltos que
 * nadie había mirado: H1→H3 en el bloque de promociones del home y en el grid del
 * catálogo (tarjetas con `<h3>` sin un `<h2>` que las agrupara), y H2→H4 en el
 * footer, que al ser componente compartido afectaba a TODAS las páginas del sitio.
 *
 * Se arreglaron con `<h2 class="sr-only">` donde el diseño no lleva título visible
 * y subiendo los títulos del footer a `<h3>`. Este test existe para que el próximo
 * cambio de marcado no los reabra.
 */
class HeadingHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /** @return list<array{0: string}> */
    public static function paginasProvider(): array
    {
        return [
            'home' => ['/es'],
            'nosotros' => ['/es/nosotros'],
            'blog' => ['/es/blog'],
            'contacto' => ['/es/contacto'],
            'catálogo' => ['/es/tours'],
            'términos' => ['/es/terminos'],
            'privacidad' => ['/es/privacidad'],
        ];
    }

    /**
     * La ficha de tour va aparte porque su URL depende de un slug de la base: un
     * data provider es estático y no puede consultarla (hardcodear un slug real dio
     * 404 en el entorno de test, que siembra su propio catálogo).
     *
     * Entró al inventario el 2026-08-12: el gate de QA la revisó por su cuenta y
     * encontró un H2→H4 que este test no cubría — el itinerario usaba `<h4>` y los
     * tours relacionados `<h5>`. Como es una plantilla compartida, el defecto estaba
     * en las 24 fichas a la vez.
     */
    public function test_la_ficha_de_tour_no_tiene_saltos_y_tiene_un_solo_h1(): void
    {
        $tour = \App\Models\Tour::published()->firstOrFail();

        // El itinerario y los tours relacionados son justamente donde estaban los
        // saltos, y el catálogo sembrado no trae itinerario: sin cargarlo, el bloque
        // no se renderiza y el test pasaría aunque el defecto siguiera ahí (probado:
        // con `itinerary_es` vacío, reintroducir el <h4> no ponía el test en rojo).
        $tour->update([
            'itinerary_es' => [
                ['time' => '08:00', 'title' => 'Recojo en el hotel', 'description' => 'Pasamos por ti.'],
                ['time' => '10:30', 'title' => 'Centro Histórico', 'description' => 'Recorrido guiado.'],
            ],
        ]);

        $this->assertGreaterThan(
            1,
            \App\Models\Tour::published()->count(),
            'La ficha necesita al menos otro tour publicado para que se pinte el bloque «Más tours», que es donde estaba el otro salto.'
        );

        $url = route('tours.show', ['locale' => 'es', 'slug' => $tour->slug], false);
        $html = $this->get($url)->assertOk()->getContent();

        $encabezados = $this->encabezados($html);
        $this->assertNotEmpty($encabezados, "No se extrajo ningún encabezado de $url.");

        $saltos = [];
        $prev = 0;

        foreach ($encabezados as [$nivel, $texto]) {
            if ($prev > 0 && $nivel > $prev + 1) {
                $saltos[] = "H{$prev}→H{$nivel} en «".mb_strimwidth($texto, 0, 40, '…').'»';
            }

            $prev = $nivel;
        }

        $this->assertSame([], $saltos, "Saltos de jerarquía en la ficha «{$tour->slug}»:\n".implode("\n", $saltos));

        $h1 = array_filter($encabezados, fn (array $h) => $h[0] === 1);
        $this->assertCount(1, $h1, 'La ficha debe tener exactamente un <h1>, tiene '.count($h1));
    }

    /**
     * @dataProvider paginasProvider
     */
    public function test_la_pagina_no_tiene_saltos_de_nivel_en_sus_encabezados(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $encabezados = $this->encabezados($html);

        // Si la extracción viene vacía, el check no midió nada: eso es un fallo, no
        // un "todo bien". Ya pasó en este proyecto con un grep sobre HTML aplanado.
        $this->assertNotEmpty($encabezados, "No se extrajo ningún encabezado de $url: el check no midió nada.");

        $saltos = [];
        $prev = 0;

        foreach ($encabezados as [$nivel, $texto]) {
            if ($prev > 0 && $nivel > $prev + 1) {
                $saltos[] = "H{$prev}→H{$nivel} en «".mb_strimwidth($texto, 0, 40, '…').'»';
            }

            $prev = $nivel;
        }

        $this->assertSame([], $saltos, "Saltos de jerarquía en $url:\n".implode("\n", $saltos));
    }

    /**
     * @dataProvider paginasProvider
     */
    public function test_la_pagina_tiene_exactamente_un_h1(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $h1 = array_filter($this->encabezados($html), fn (array $h) => $h[0] === 1);

        $this->assertCount(1, $h1, "$url debe tener exactamente un <h1>, tiene ".count($h1));
    }

    /**
     * El footer es compartido: si sus títulos vuelven a `<h4>`, el salto reaparece en
     * todo el sitio. Se comprueba aparte para que el motivo del rojo sea evidente.
     */
    public function test_los_titulos_del_footer_no_bajan_de_h3(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('<h3 id="footer-links"', $html);
        $this->assertStringNotContainsString('<h4 id="footer-links"', $html);
        $this->assertStringNotContainsString('<h4 id="footer-contact"', $html);
        $this->assertStringNotContainsString('<h4 id="footer-tours"', $html);
    }

    /**
     * @return list<array{0: int, 1: string}>
     */
    private function encabezados(string $html): array
    {
        // Comillas simples a propósito: con comillas dobles, "\1" es un byte octal en
        // PHP y la backreference se rompe en silencio → 0 coincidencias.
        preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER);

        return array_map(
            fn (array $m) => [(int) $m[1], trim(preg_replace('/\s+/', ' ', strip_tags($m[2])))],
            $matches
        );
    }
}
