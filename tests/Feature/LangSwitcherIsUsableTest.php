<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El selector de idioma, que el jefe reportó dos veces el 2026-08-21 y el
 * 2026-08-24 ("Idioma", "la bandera se ve por debajo en el menú", "no es
 * legible el menú de las banderas ni idioma"). Eran TRES defectos distintos
 * encadenados, y los dos últimos solo se vieron cuando se arregló el primero:
 *
 *  1. El panel quedaba DEBAJO del botón "Reservar Ahora" (z-index) y solo
 *     asomaba la franja con la bandera. Arreglado con `.lat-lang-menu`.
 *  2. Destapado el panel, se vio que los enlaces salían BLANCOS SOBRE BLANCO:
 *     `.lat-topbar a { color: rgba(255,255,255,.92) }` le gana por
 *     especificidad al `text-lat-ink` del <ul>.
 *  3. En el menú móvil el mismo dropdown se abría 232 px por DEBAJO del borde
 *     inferior de la pantalla (el componente vive al fondo de un panel con
 *     `overflow-y: auto`), así que no había nada que leer.
 */
class LangSwitcherIsUsableTest extends TestCase
{
    use RefreshDatabase;

    private function drawer(string $html): string
    {
        $start = strpos($html, 'lat-drawer__foot');
        $this->assertNotFalse($start, 'No se encontró el pie del menú móvil.');

        return substr($html, $start, strpos($html, '</header>', $start) - $start);
    }

    public function test_el_menu_movil_muestra_los_tres_idiomas_sin_desplegable(): void
    {
        $drawer = $this->drawer($this->get('/es')->assertOk()->getContent());

        $this->assertStringContainsString('lat-lang-inline', $drawer,
            'El menú móvil volvió al dropdown, que se abre fuera de la pantalla.');
        $this->assertStringNotContainsString('role="listbox"', $drawer,
            'Hay un desplegable dentro del drawer: al fondo de un panel scrollable, su panel cae fuera del viewport.');

        // Bandera Y nombre, no un código de dos letras: lo que el jefe pidió
        // es que se LEA.
        foreach (['Español', 'English', 'Português'] as $idioma) {
            $this->assertStringContainsString($idioma, $drawer, "Falta «{$idioma}» en el menú móvil.");
        }

        foreach (['/es', '/en', '/pt'] as $url) {
            $this->assertStringContainsString($url . '"', $drawer, "Falta el enlace a {$url}.");
        }
    }

    public function test_el_idioma_activo_va_marcado_en_el_menu_movil(): void
    {
        $drawer = $this->drawer($this->get('/en')->assertOk()->getContent());

        // El marcado no es solo visual: `aria-current` es lo que oye un lector
        // de pantalla, y el `is-active` es lo que pinta la píldora.
        $this->assertMatchesRegularExpression('#<a[^>]*hreflang="en"[^>]*is-active#s', $drawer,
            'En /en el idioma activo no queda marcado.');
        $this->assertStringContainsString('aria-current="true"', $drawer);
    }

    public function test_el_desplegable_de_escritorio_sigue_existiendo(): void
    {
        // El modo en línea es SOLO para el drawer: en la topbar el dropdown se
        // queda (ahí hay sitio y no lo recorta nada).
        $html = $this->get('/es')->assertOk()->getContent();
        $topbar = substr($html, strpos($html, 'lat-topbar__right'), 3000);

        $this->assertStringContainsString('role="listbox"', $topbar);
        $this->assertStringContainsString('lat-lang-menu', $topbar);
    }

    /**
     * Este test lee el SCSS y no el HTML a propósito: el defecto era de
     * CASCADA, no de marcado, y no hay forma de verlo en el HTML servido. La
     * regla que lo evita es la única línea que impide que los enlaces del
     * panel vuelvan a heredar el blanco de `.lat-topbar a`.
     */
    public function test_el_panel_de_idioma_fija_el_color_de_su_texto(): void
    {
        $scss = file_get_contents(resource_path('scss/layouts/_lat-header.scss'));

        $inicio = strpos($scss, '.lat-lang-menu {');
        $this->assertNotFalse($inicio, 'Desapareció la regla .lat-lang-menu.');
        $bloque = substr($scss, $inicio, 1400);

        $this->assertStringContainsString('z-index: 9300', $bloque,
            'Sin z-index por encima del header, el botón "Reservar Ahora" vuelve a tapar el panel.');
        $this->assertMatchesRegularExpression('#a,\s*\n\s*a:hover,\s*\n\s*a:focus\s*\{\s*\n\s*color: \$lat-ink;#', $bloque,
            'Sin esta regla los enlaces del panel heredan el blanco de .lat-topbar a → blanco sobre blanco.');
    }
}
