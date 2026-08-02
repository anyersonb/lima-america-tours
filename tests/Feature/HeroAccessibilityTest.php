<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dos defectos de accesibilidad del hero, ambos medibles sin navegador:
 *
 *  1) FOCO INVISIBLE en el buscador. `.lat-search input, select` traía
 *     `&:focus { outline: none }` sin ninguna alternativa visible: quien navega
 *     con teclado no sabía en qué campo está. Se reemplaza por un anillo en el
 *     contenedor del campo (`:focus-within` / `:focus-visible`).
 *
 *  2) CONTRASTE del pill verde de WhatsApp. Texto blanco sobre el verde de
 *     marca de WhatsApp (#25d366) da 1.98:1 — muy por debajo del 4.5:1 de AA
 *     para texto normal. El test calcula el ratio real desde el token SCSS, así
 *     que si alguien vuelve a aclarar el verde, falla.
 *
 * Antes del fix: fallan los dos.
 * Después del fix: pasan.
 */
class HeroAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    private function scss(string $relative): string
    {
        $path = base_path($relative);
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }

    /** Luminancia relativa WCAG de un hex. */
    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [];

        foreach ([0, 2, 4] as $offset) {
            $c = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private function contrastWithWhite(string $hex): float
    {
        return round((1.0 + 0.05) / ($this->luminance($hex) + 0.05), 2);
    }

    public function test_search_fields_have_a_visible_focus_ring(): void
    {
        $scss = $this->scss('resources/scss/pages/_lat-home.scss');

        $start = strpos($scss, '.lat-search {');
        $this->assertNotFalse($start, 'No se encontró el bloque .lat-search en el SCSS del home.');

        $block = substr($scss, $start, 3000);

        $this->assertMatchesRegularExpression(
            '/focus-within|focus-visible/',
            $block,
            'El buscador del hero no declara ningún estilo de foco visible (:focus-within / :focus-visible).'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/&:focus\s*\{\s*outline:\s*none;\s*color/',
            $block,
            'Sigue el `:focus { outline: none }` que dejaba el foco invisible en los campos del buscador.'
        );
    }

    public function test_whatsapp_pill_green_passes_aa_against_white_text(): void
    {
        $vars = $this->scss('resources/scss/abstracts/_variables.scss');

        preg_match('/\$lat-wa-strong:\s*(#[0-9a-fA-F]{6})/', $vars, $m);
        $this->assertNotEmpty(
            $m[1] ?? '',
            'Falta el token $lat-wa-strong (verde de WhatsApp con contraste suficiente para texto blanco).'
        );

        $ratio = $this->contrastWithWhite($m[1]);

        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "El verde del pill de WhatsApp da {$ratio}:1 con texto blanco; AA para texto normal pide 4.5:1."
        );
    }

    /**
     * Barre TODOS los parciales: `.lat-btn--wa` está declarado en dos archivos
     * (_lat-home y _lat-about) y el segundo se importa después, así que gana la
     * cascada. Corregir solo el del hero no cambia nada en pantalla — pasó de
     * verdad durante este fix, con el test verde y el navegador mostrando el
     * verde viejo. Por eso el test ya no mira un archivo: mira todos.
     */
    public function test_every_whatsapp_button_with_white_text_uses_the_accessible_green(): void
    {
        $files = glob(base_path('resources/scss/**/*.scss')) ?: [];
        $offenders = [];

        foreach ($files as $file) {
            $scss = (string) file_get_contents($file);

            // Cada uso del verde de marca como fondo. El (?![\w-]) es
            // indispensable: sin él, `$lat-wa-strong` también hace match (el
            // guion cuenta como límite de palabra) y el test da falsos
            // positivos sobre el código ya corregido.
            preg_match_all('/background:\s*\$lat-wa(?![\w-])/', $scss, $m, PREG_OFFSET_CAPTURE);

            foreach ($m[0] as [$match, $offset]) {
                // Ventana corta alrededor de la declaración: se busca el `color`
                // de ESA regla, no el de reglas anidadas más abajo (que es lo
                // que ensuciaba el resultado al partir por selector).
                $window = substr($scss, max(0, $offset - 160), 320);

                if (preg_match('/color:\s*(#fff\b|#ffffff\b|white\b)/i', $window)) {
                    $line = substr_count(substr($scss, 0, $offset), "\n") + 1;
                    $offenders[] = basename($file).':'.$line;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Hay botones con texto blanco sobre \$lat-wa (1.98:1). Deben usar \$lat-wa-strong:\n- ".implode("\n- ", $offenders)
        );
    }

    /**
     * El FAB flotante quedó como ÚNICO CTA de WhatsApp del sitio (el pill del
     * hero se retiró el 2026-07-29), y su color va inline en el layout, no en el
     * SCSS: el glifo blanco sobre el verde de marca da 1.98:1 y WCAG 1.4.11 pide
     * 3:1 para elementos gráficos.
     */
    public function test_floating_whatsapp_button_uses_the_accessible_green(): void
    {
        $layout = (string) file_get_contents(base_path('resources/views/layouts/app.blade.php'));

        $start = strpos($layout, 'id="waFab"');
        $this->assertNotFalse($start, 'No se encontró el FAB de WhatsApp en el layout.');

        $block = substr($layout, (int) $start, 1200);

        preg_match('/background-color:\s*(#[0-9a-fA-F]{6})/', $block, $m);
        $this->assertNotEmpty($m[1] ?? '', 'El FAB no declara un color de fondo explícito.');

        $ratio = $this->contrastWithWhite($m[1]);

        $this->assertGreaterThanOrEqual(
            3.0,
            $ratio,
            "El verde del FAB da {$ratio}:1 contra el glifo blanco; WCAG 1.4.11 pide 3:1."
        );
    }

    public function test_hero_photo_has_a_descriptive_alt(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        // 2026-08: .lat-hero__media se renombró a .lat-hero__bg (rediseño
        // "foto a sangre") — mismo contrato, solo cambia el nombre.
        $mediaPos = strpos($html, 'lat-hero__bg');
        $imgPos = strpos($html, '<img', (int) $mediaPos);
        $img = substr($html, (int) $imgPos, strpos($html, '>', (int) $imgPos) - $imgPos + 1);

        preg_match('/\salt="([^"]*)"/', $img, $m);
        $alt = $m[1] ?? '';

        $this->assertNotSame('', trim($alt), 'La foto del hero (LCP) no tiene alt.');
        $this->assertGreaterThan(20, mb_strlen($alt), "El alt del hero es demasiado pobre: \"{$alt}\"");
    }

    public function test_search_inputs_are_labelled(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        // Los 4 campos del buscador tienen <label for> apuntando a su control.
        foreach (['s-q', 's-dest', 's-date', 's-pax'] as $id) {
            $this->assertMatchesRegularExpression(
                '/<label[^>]*for="'.$id.'"/',
                $html,
                "El campo #{$id} del buscador no tiene <label for>."
            );
            $this->assertMatchesRegularExpression(
                '/id="'.$id.'"/',
                $html,
                "No existe el control #{$id} al que apunta su label."
            );
        }
    }

    public function test_video_pill_is_not_rendered_when_there_is_no_video(): void
    {
        Setting::set('home_hero_video_url', '');

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString('heroVideoBtn', $html, 'Sin URL de video no debe quedar un botón muerto.');
    }
}
