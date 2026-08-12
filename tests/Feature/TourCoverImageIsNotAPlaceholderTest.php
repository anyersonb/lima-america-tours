<?php

namespace Tests\Feature;

use App\Models\Tour;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contraparte de RegionHeroImageIsNotAPlaceholderTest, para tours.
 *
 * Hallazgo del jefe (2026-08-11), verificado con PDO directo contra
 * `lima_america` (no Eloquent, no scopes):
 *
 *   SELECT COUNT(*) FROM tours WHERE cover_image LIKE '%Rectangle%'; -- 5
 *
 * Esos 5 covers (más su propia `gallery`) apuntaban al kit de mockup:
 * assets/banners/Rectangle 192XX.jpg, 205×123px reales servidos estirados a
 * ~1900px. Corregido en TourSeeder (fuente para instalaciones nuevas/CI) y
 * con TourCoverImageFixSeeder (backfill para la BD ya sembrada).
 *
 * Como el catálogo real SÍ tiene fotos legítimas por debajo de 600px (ver
 * tours #13, #16, #20... con fotos de 350-598px que son contenido real, no
 * placeholders), este test no exige un mínimo "bonito" arbitrario: exige
 * que ningún tour publicado (ni su cover_image ni ninguna entrada de su
 * gallery) siga apuntando al placeholder conocido, verificado de dos formas
 * independientes que una futura regresión no puede burlar a la vez:
 *
 *  1. El nombre de archivo no contiene "Rectangle" ni termina en
 *     "/image.jpg" o "/image-1.jpg" (las firmas exactas del kit degradado).
 *  2. El archivo real en disco no mide menos de 300px de ancho — el umbral
 *     se fijó mirando el catálogo completo de tours publicados hoy (ninguno
 *     baja de 358px); el placeholder conocido mide 205px. Un futuro
 *     reemplazo accidental por OTRO archivo diminuto también lo atraparía,
 *     aunque no se llame "Rectangle".
 */
class TourCoverImageIsNotAPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    private const MIN_WIDTH = 300;

    public function test_no_published_tour_uses_the_tiny_mockup_placeholder(): void
    {
        (new DatabaseSeeder)->run();

        $tours = Tour::published()->get();
        $this->assertGreaterThan(0, $tours->count(), 'Debe haber al menos un tour publicado para que este test verifique algo.');

        foreach ($tours as $tour) {
            $paths = array_filter(array_merge(
                [$tour->cover_image],
                is_array($tour->gallery) ? $tour->gallery : []
            ));

            $this->assertNotEmpty(
                $paths,
                "El tour '{$tour->slug}' no tiene ni cover_image ni gallery: no se puede verificar, revisar a mano."
            );

            foreach ($paths as $path) {
                $this->assertPathIsNotAPlaceholder($path, $tour->slug);
            }
        }
    }

    private function assertPathIsNotAPlaceholder(string $path, string $tourSlug): void
    {
        $this->assertStringNotContainsString(
            'Rectangle',
            $path,
            "El tour '{$tourSlug}' sigue apuntando al placeholder Rectangle ({$path})."
        );
        $this->assertFalse(
            str_ends_with($path, '/image.jpg') || str_ends_with($path, '/image-1.jpg'),
            "El tour '{$tourSlug}' sigue apuntando al placeholder genérico ({$path})."
        );

        $absolutePath = str_starts_with($path, 'assets/')
            ? public_path($path)
            : storage_path('app/public/'.$path);

        $this->assertFileExists($absolutePath, "El tour '{$tourSlug}' referencia un archivo que no existe: {$path}");

        $size = getimagesize($absolutePath);
        $this->assertNotFalse($size, "No se pudo leer la imagen del tour '{$tourSlug}': {$path}");
        $this->assertGreaterThanOrEqual(
            self::MIN_WIDTH,
            $size[0],
            "El tour '{$tourSlug}' usa una imagen de solo {$size[0]}px de ancho ({$path}) — parece un placeholder chico."
        );
    }
}
