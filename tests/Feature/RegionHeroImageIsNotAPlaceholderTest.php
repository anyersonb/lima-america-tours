<?php

namespace Tests\Feature;

use App\Models\Region;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo del jefe revisando el lote de destinos (2026-08-10): las 3
 * regiones reales (Lima, Ica, Cusco) tenían `hero_image` apuntando a
 * assets/banners/Rectangle 192{16,18,19}.jpg — placeholders de 205×123px
 * que se ven borrosos estirados a ancho completo en el grid de destinos.
 * Se reemplazaron por fotos reales del propio catálogo (≥1900px de ancho).
 *
 * Este test no solo mira el string de la ruta: abre el archivo real en
 * disco y mide el ancho, para que una futura regresión a un placeholder
 * chico (aunque no se llame "Rectangle") también la detecte.
 */
class RegionHeroImageIsNotAPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    private const MIN_WIDTH = 600;

    public function test_seeded_regions_do_not_use_the_tiny_banner_placeholders(): void
    {
        (new DatabaseSeeder)->run();

        $regions = Region::whereIn('slug', ['lima', 'ica', 'cusco'])->get();
        $this->assertCount(3, $regions, 'Las 3 regiones reales deben existir tras sembrar.');

        foreach ($regions as $region) {
            $this->assertStringNotContainsString(
                'Rectangle',
                $region->hero_image,
                "La región {$region->slug} sigue apuntando al placeholder Rectangle."
            );

            $absolutePath = storage_path('app/public/'.$region->hero_image);
            $this->assertFileExists($absolutePath, "El hero_image de {$region->slug} no existe en disco: {$region->hero_image}");

            $size = getimagesize($absolutePath);
            $this->assertNotFalse($size, "No se pudo leer el archivo de imagen de {$region->slug}.");
            $this->assertGreaterThanOrEqual(
                self::MIN_WIDTH,
                $size[0],
                "El hero_image de {$region->slug} mide solo {$size[0]}px de ancho — sigue siendo un placeholder chico."
            );
        }
    }
}
