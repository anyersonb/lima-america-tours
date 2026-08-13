<?php

namespace App\Services;

use App\Models\HeroSlide;
use App\Support\ResponsiveImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Arma `$heroSlides` para el slider administrable del hero de la home
 * (mockup `01-home.jpeg`: 4 puntos + flechas ←→, docs/rebrand/ESTADO.md
 * "Faltante de alcance, no defecto"). Contrato con el maquetador: una
 * colección ORDENADA donde cada elemento trae `url`/`srcset`/`sizes` (ya
 * pasados por ResponsiveImage, mismas variantes que el hero de hoy — no se
 * degrada el LCP), `alt` en el idioma actual, y `is_first` (true solo en la
 * primera, la única con carga prioritaria).
 *
 * Regla 1 del contrato — NUNCA vacía: si no hay diapositivas activas en la
 * tabla `hero_slides`, se devuelve una sola con EXACTAMENTE la imagen que
 * el hero muestra hoy (fallback ya calculado por home.blade.php a partir de
 * `home_hero_image`, incluido su propio fallback y su alt). Esa lógica NO
 * se duplica aquí a propósito — se recibe ya resuelta como parámetro, para
 * que exista una sola fuente de verdad y las dos rutas no puedan divergir.
 */
class HeroSlidesResolver
{
    /**
     * @param  array{src:string,srcset:string,sizes:string,width:?int,height:?int}  $fallbackImage  Salida de ResponsiveImage::make() para home_hero_image (con su propio fallback ya resuelto).
     * @param  string  $fallbackAlt  Alt ya resuelto para home_hero_image (Setting o default).
     * @return Collection<int, array{url:string,srcset:string,sizes:string,width:?int,height:?int,alt:string,is_first:bool}>
     */
    public function resolve(string $locale, array $fallbackImage, string $fallbackAlt): Collection
    {
        $slides = HeroSlide::active()->ordered()->get();

        if ($slides->isEmpty()) {
            return collect([[
                'url' => $fallbackImage['src'],
                'srcset' => $fallbackImage['srcset'],
                'sizes' => $fallbackImage['sizes'],
                'width' => $fallbackImage['width'],
                'height' => $fallbackImage['height'],
                'alt' => $fallbackAlt,
                'is_first' => true,
            ]]);
        }

        return $slides->values()->map(
            fn (HeroSlide $slide, int $index) => $this->fromModel($slide, $index === 0)
        );
    }

    /**
     * @return array{url:string,srcset:string,sizes:string,width:?int,height:?int,alt:string,is_first:bool}
     */
    private function fromModel(HeroSlide $slide, bool $isFirst): array
    {
        [$sourcePath, $url] = $this->resolvePaths(trim((string) $slide->image));

        // Mismas variantes/anchos que usa hoy la imagen del hero (HERO_WIDTHS
        // por defecto de ResponsiveImage) para no arruinar el LCP: la
        // primera diapositiva es la que va precargada y con fetchpriority
        // alto, así que tiene que pesar lo mismo que la foto única de hoy.
        $image = ResponsiveImage::make($sourcePath, $url);

        return [
            'url' => $image['src'],
            'srcset' => $image['srcset'],
            'sizes' => $image['sizes'],
            'width' => $image['width'],
            'height' => $image['height'],
            'alt' => $slide->alt,
            'is_first' => $isFirst,
        ];
    }

    /**
     * Mismo criterio de dos convenciones que usa home.blade.php para
     * `home_hero_image`: rutas "assets/..." son archivos de repositorio
     * (seeder), cualquier otra ruta es una subida vía FileUpload en el
     * disco "media" (directorio "home").
     *
     * @return array{0:string,1:string} [rutaFísicaAbsoluta, urlPública]
     */
    private function resolvePaths(string $path): array
    {
        if ($path === '') {
            return [public_path(ResponsiveImage::DEFAULT_PHOTO), asset(ResponsiveImage::DEFAULT_PHOTO)];
        }

        if (str_starts_with($path, 'assets/') || str_starts_with($path, '/')) {
            return [public_path(ltrim($path, '/')), asset($path)];
        }

        return [public_path('media/'.ltrim($path, '/')), Storage::disk('media')->url($path)];
    }
}
