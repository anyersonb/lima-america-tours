<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo CRO [Alto], lote agosto 2026: las tarjetas de categoría del Home
 * (tira encimada al hero + "Explora por categoría") apuntaban al catálogo
 * completo (route('tours.index')) en vez de a un listado ya filtrado — el
 * usuario veía "3 tours" en la tarjeta y aterrizaba en los 24.
 *
 * El filtrado de /tours es client-side (botones .lat-filter con data-cat
 * dentro de #tourFilters, tarjetas .lat-tcard con data-cat, JS con
 * curCat = 'all'). NO se usa route('tours.category', ...): esa ruta es de
 * REGIONES (constraint lima|ica|cusco, Region::where('slug', ...)), no de
 * categorías — pasarle un slug de categoría no genera la URL o cae en 404.
 *
 * El fix real: las tarjetas del Home enlazan a tours.index con ?cat={slug},
 * y el script de tours/index.blade.php lee ese querystring al cargar,
 * preselecciona el botón (.is-active) y filtra, igual que un clic manual.
 * Si el slug no existe entre los botones reales, se ignora sin romper nada
 * (el listado se queda en "Todos", que ya nace is-active en el HTML).
 */
class TourCategoryQuerystringFilterTest extends TestCase
{
    use RefreshDatabase;

    private function seedCategoryWithTours(string $slug, string $name, int $tours = 1): Category
    {
        $cat = Category::create([
            'slug' => $slug,
            'name_es' => $name,
            'name_en' => $name,
            'is_active' => true,
            'order' => 1,
        ]);
        Tour::factory()->count($tours)->create(['category_id' => $cat->id, 'is_published' => true]);

        return $cat;
    }

    public function test_home_category_cards_link_to_tours_index_with_cat_querystring(): void
    {
        $this->seedCategoryWithTours('aventura', 'Aventura', 2);

        $response = $this->get('/es');
        $response->assertOk();

        $expectedUrl = route('tours.index', ['locale' => 'es', 'cat' => 'aventura']);
        $this->assertStringContainsString('cat=aventura', $expectedUrl);

        // Debe aparecer en LAS DOS tarjetas de categoría del Home: la tira
        // encimada al hero y "Explora por categoría" — no basta con que una
        // de las dos lleve el querystring y la otra siga apuntando al
        // catálogo completo.
        $needle = 'href="'.$expectedUrl.'"';
        $this->assertSame(
            2,
            substr_count($response->getContent(), $needle),
            "esperaba el href con ?cat=aventura en las 2 tarjetas de categoría del Home"
        );

        // Nunca la ruta de regiones/destinos con el slug de la categoría:
        // esa ruta usa Region::where('slug', ...) y un slug de categoría
        // real (ej. "aventura") no es una región, así que caería en 404.
        $response->assertDontSee(route('tours.category', ['locale' => 'es', 'categoria' => 'aventura']), false);
    }

    public function test_tours_index_page_carries_the_querystring_preselection_script(): void
    {
        $this->seedCategoryWithTours('culinarias', 'Culinarias', 3);

        $response = $this->get('/es/tours?cat=culinarias');
        $response->assertOk();

        $html = $response->getContent();

        // El botón real de la categoría tiene que existir para que el JS lo encuentre.
        $this->assertStringContainsString('data-cat="culinarias"', $html);

        // El mecanismo de preselección debe viajar en la página: lee el
        // querystring, busca el botón y dispara el mismo filtrado que un
        // clic manual (applyFilters), sin depender de que un test HTTP
        // pueda ejecutar JS de verdad.
        $this->assertStringContainsString('URLSearchParams(window.location.search)', $html);
        $this->assertStringContainsString(".get('cat')", $html);
        $this->assertStringContainsString('applyFilters();', $html);
    }

    public function test_unknown_cat_querystring_falls_back_to_todos_without_breaking_the_page(): void
    {
        $this->seedCategoryWithTours('otros', 'Otros', 1);

        $response = $this->get('/es/tours?cat=no-existe-esta-categoria');
        $response->assertOk();

        $html = $response->getContent();

        // No hay botón para ese slug: el JS no tiene con qué preseleccionar
        // y se queda en "Todos" (ya viene is-active por defecto en el HTML).
        $this->assertStringNotContainsString('data-cat="no-existe-esta-categoria"', $html);
        $this->assertStringContainsString('lat-filter is-active" data-cat="all"', $html);

        // El grid sigue sirviendo el catálogo completo desde el servidor
        // (el filtrado es client-side): no debe quedar vacío.
        $response->assertSee('lat-tcard', false);
    }
}
