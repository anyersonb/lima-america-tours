<?php

namespace Tests\Feature;

use App\Models\Tour;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Hallazgo del jefe (2026-08-11): dos tours con pinta de debug/QA quedaron
 * con `is_published=1` en la BD real de desarrollo (`lima_america`):
 *
 *   #7  slug=tour-qa-playwright-de-prueba  título="Tour QA Playwright de Prueba"
 *   #35 slug=dbg-cap                       título="DBG CAP" (precio $10)
 *
 * Ninguno de los dos llegó a verse en el sitio público — por casualidad
 * ambos también terminaron `deleted_at` no nulo (soft-deleted), y
 * `Tour::published()` respeta ese scope — pero nada en el código lo exigía;
 * fue coincidencia, no diseño. Se despublicaron ambos como medida de
 * seguridad (por si algún día alguien los restaura desde la papelera de
 * Filament) y este test es el guardrail de regresión: ningún tour con pinta
 * de dato de prueba debe quedar publicado.
 *
 * Se descartó una versión más agresiva (un guard en `Tour::booted()` que
 * bloqueara `is_published=true` en el propio modelo, a nivel de guardado):
 * al probarlo, rompió `TourShowTitlePreservesCasingTest` y otros tests que
 * crean legítimamente tours con "prueba"/"QA" en el título en español para
 * probar OTRA cosa (casing, moneda, precio) — un guard global habría
 * castigado justo el vocabulario que un suite de pruebas en español usa
 * todo el tiempo. Por eso este test solo vigila los datos que produce
 * `DatabaseSeeder` (el mismo que corre un fresh install/CI), igual que
 * `RegionHeroImageIsNotAPlaceholderTest`/`TourCoverImageIsNotAPlaceholderTest`:
 * no interfiere con fixtures ad-hoc de otros tests, cada uno en su propia
 * transacción aislada por `RefreshDatabase`.
 */
class TourTestDataIsNotPublishedTest extends TestCase
{
    use RefreshDatabase;

    private const NEEDLES = ['dbg', 'debug', 'test', 'prueba', 'pruebas', 'playwright', 'qa'];

    public function test_no_published_tour_from_the_seeder_looks_like_test_or_debug_data(): void
    {
        (new DatabaseSeeder)->run();

        $tours = Tour::published()->get();
        $this->assertGreaterThan(0, $tours->count(), 'Debe haber al menos un tour publicado para que este test verifique algo.');

        foreach ($tours as $tour) {
            $this->assertFalse(
                $this->looksLikeTestData($tour->slug, $tour->title_es),
                "El tour publicado '{$tour->slug}' ({$tour->title_es}) tiene pinta de dato de prueba/debug."
            );
        }
    }

    /**
     * Misma heurística de palabra completa (no subcadena) que se decidió NO
     * meter en el modelo: "test"/"qa" como subcadena caerían dentro de
     * palabras españolas legítimas ("testimonio", "comprueba").
     */
    private function looksLikeTestData(string $slug, string $titleEs): bool
    {
        $haystack = Str::lower(str_replace('-', ' ', $slug).' '.$titleEs);

        foreach (self::NEEDLES as $needle) {
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack)) {
                return true;
            }
        }

        return false;
    }
}
