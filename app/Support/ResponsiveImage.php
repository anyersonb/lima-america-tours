<?php

namespace App\Support;

/**
 * Derivados WebP responsive para imágenes grandes servidas en el front.
 *
 * Nació por la foto del hero de la home, que es el LCP de todo el sitio: se
 * servía el archivo tal cual, en un solo tamaño (el fallback es un PNG de
 * 2.52 MB), así que un móvil de 375 px descargaba la imagen de escritorio
 * completa. Aquí se generan variantes WebP a varios anchos, una vez, y se
 * cachean en disco (`public/media/derived`) con una firma del archivo de
 * origen: si el admin sube otra foto desde el panel, cambia la firma y se
 * regeneran solas.
 *
 * Reglas que se respetan a propósito:
 *  - Nunca se agranda: si el origen mide menos que el ancho pedido, se omite
 *    esa variante (una imagen escalada hacia arriba pesa más y se ve peor).
 *  - `width`/`height` devueltos son los REALES del archivo que se sirve como
 *    `src`, no valores fijos en el Blade — así el navegador reserva la caja
 *    exacta y no hay salto de layout (CLS) ni metadata mentirosa.
 *  - Si algo falla (GD sin WebP, archivo ilegible, disco de solo lectura), se
 *    devuelve la URL original y `srcset` vacío: la página nunca se cae por una
 *    optimización de imagen.
 *
 * Usa GD nativo, igual que `ImageOptimizer` (que se encarga del otro extremo:
 * las subidas del CMS). Este no reemplaza a aquel — `ImageOptimizer` normaliza
 * lo que entra, `ResponsiveImage` genera los tamaños de lo que sale.
 */
class ResponsiveImage
{
    /**
     * Anchos de las variantes del hero: móvil, tablet/laptop y desktop.
     *
     * El techo es 1600 a propósito, aunque la foto por defecto mida 1920: esta
     * panorámica es muy densa en detalle (follaje y piedra) y a 1920 no baja de
     * ~315 KB ni bajando la calidad a 48, donde ya se degrada a la vista. A
     * 1600 cabe en el presupuesto del LCP y cubre un escritorio de 1440 a 1x
     * sin escalar hacia arriba. Subir este techo obliga a medir el peso otra
     * vez: hay un test que lo vigila.
     */
    public const HERO_WIDTHS = [640, 1024, 1600];

    /** El hero ocupa el ancho completo del viewport en todos los breakpoints. */
    public const HERO_SIZES = '100vw';

    /** Carpeta de caché, relativa a public/. */
    public const CACHE_DIR = 'media/derived';

    /**
     * Foto por defecto del sitio (hero de la home y fallback de secciones sin
     * imagen propia). Ruta relativa a public/.
     */
    public const DEFAULT_PHOTO = 'assets/banners/hero-machu-picchu-pano.jpg';

    /**
     * URL de la foto por defecto en una variante WebP del ancho pedido.
     *
     * Existe para los usos de RELLENO (fondo del footer, tarjetas de categoría
     * u ofertas sin imagen cargada). Antes esos tres sitios apuntaban al PNG de
     * 2.52 MB, así que la home lo descargaba entero aunque el hero ya se
     * sirviera optimizado: medido en el navegador, 2522 KB de transferencia en
     * una sola petición. Pedir aquí el ancho REAL en que se muestra la imagen
     * reutiliza variantes ya generadas y baja eso a decenas de KB.
     */
    public static function defaultPhotoUrl(int $width = 1024): string
    {
        return self::make(
            public_path(self::DEFAULT_PHOTO),
            asset(self::DEFAULT_PHOTO),
            [$width]
        )['src'];
    }

    /**
     * @param  string  $absolutePath  Ruta física del archivo de origen.
     * @param  string  $originalUrl   URL con la que se serviría sin optimizar.
     * @param  int[]   $widths        Anchos deseados.
     * @return array{src:string,srcset:string,sizes:string,width:?int,height:?int}
     */
    public static function make(
        string $absolutePath,
        string $originalUrl,
        array $widths = self::HERO_WIDTHS,
        string $sizes = self::HERO_SIZES,
        int $quality = 78
    ): array {
        $fallback = [
            'src' => $originalUrl,
            'srcset' => '',
            'sizes' => $sizes,
            'width' => null,
            'height' => null,
        ];

        if (! is_file($absolutePath)) {
            return $fallback;
        }

        $dimensions = @getimagesize($absolutePath);

        if ($dimensions === false) {
            return $fallback;
        }

        [$sourceWidth, $sourceHeight] = $dimensions;
        $fallback['width'] = (int) $sourceWidth;
        $fallback['height'] = (int) $sourceHeight;

        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return $fallback;
        }

        $targets = self::targetWidths($widths, (int) $sourceWidth);

        if ($targets === []) {
            return $fallback;
        }

        $signature = substr(sha1($absolutePath.'|'.@filemtime($absolutePath).'|'.@filesize($absolutePath)), 0, 10);
        $stem = self::stem($absolutePath);

        $candidates = [];

        foreach ($targets as $width) {
            $relative = self::CACHE_DIR.'/'.$stem.'-'.$signature.'-'.$width.'.webp';
            $destination = public_path($relative);

            if (! is_file($destination) && ! self::render($absolutePath, $destination, $width, self::qualityFor($width, $quality))) {
                continue;
            }

            $size = @getimagesize($destination);

            if ($size === false) {
                continue;
            }

            $candidates[$width] = [
                'url' => asset($relative),
                'width' => (int) $size[0],
                'height' => (int) $size[1],
            ];
        }

        if ($candidates === []) {
            return $fallback;
        }

        ksort($candidates);
        $largest = end($candidates);

        return [
            'src' => $largest['url'],
            'srcset' => implode(', ', array_map(
                fn (array $c) => $c['url'].' '.$c['width'].'w',
                $candidates
            )),
            'sizes' => $sizes,
            'width' => $largest['width'],
            'height' => $largest['height'],
        ];
    }

    /**
     * Anchos a generar: los pedidos que quepan en el origen, más el propio
     * techo (el menor entre el ancho máximo pedido y el ancho real del archivo)
     * para que la variante grande nunca sea un upscale.
     *
     * @param  int[]  $widths
     * @return int[]
     */
    private static function targetWidths(array $widths, int $sourceWidth): array
    {
        $widths = array_values(array_filter(array_map('intval', $widths), fn (int $w) => $w > 0));

        if ($widths === [] || $sourceWidth <= 0) {
            return [];
        }

        $targets = array_filter($widths, fn (int $w) => $w < $sourceWidth);
        $targets[] = min(max($widths), $sourceWidth);

        $targets = array_values(array_unique($targets));
        sort($targets);

        return $targets;
    }

    /**
     * Calidad WebP según el ancho: cuanto más grande la variante, menos calidad
     * por píxel hace falta para que se vea igual (el ojo no resuelve el detalle
     * fino cuando la imagen se muestra a esa escala) y más caro sale cada punto
     * de calidad. Sin esta escalera, la panorámica del hero salía en 476 KB a
     * 1920 px — el triple del presupuesto para el LCP.
     */
    private static function qualityFor(int $width, int $base): int
    {
        $quality = match (true) {
            $width >= 1800 => $base - 20,
            $width >= 1400 => $base - 12,
            default => $base,
        };

        return max(45, min(95, $quality));
    }

    /** Nombre base legible y seguro para el archivo derivado. */
    private static function stem(string $absolutePath): string
    {
        $stem = pathinfo($absolutePath, PATHINFO_FILENAME);
        $stem = strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $stem));
        $stem = trim($stem, '-');

        return $stem === '' ? 'img' : substr($stem, 0, 40);
    }

    /**
     * Genera una variante. Escribe primero un temporal y luego renombra, para
     * que dos peticiones concurrentes no dejen un WebP a medio escribir
     * (que el navegador cachearía como imagen corrupta).
     */
    private static function render(string $source, string $destination, int $width, int $quality): bool
    {
        try {
            $directory = dirname($destination);

            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                return false;
            }

            $raw = @file_get_contents($source);

            if ($raw === false) {
                return false;
            }

            $image = @imagecreatefromstring($raw);

            if ($image === false) {
                return false;
            }

            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);

            if ($width < $sourceWidth) {
                $height = (int) round($sourceHeight * ($width / $sourceWidth));
                $resized = imagecreatetruecolor($width, max(1, $height));
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, max(1, $height), $sourceWidth, $sourceHeight);
                imagedestroy($image);
                $image = $resized;
            } else {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            $temporary = $destination.'.'.getmypid().'.tmp';
            $written = @imagewebp($image, $temporary, $quality);
            imagedestroy($image);

            if (! $written) {
                @unlink($temporary);

                return false;
            }

            if (! @rename($temporary, $destination)) {
                @unlink($temporary);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
