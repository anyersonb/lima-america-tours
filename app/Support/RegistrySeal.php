<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Caja de render del sello "Agencia de viajes y turismo registrada".
 *
 * El manual de marca que entregó el cliente (2026-08-25) fija DOS tamaños
 * para medios digitales, y no son intercambiables:
 *
 *   · versión vertical   → 150 × 250 px
 *   · versión horizontal → 300 × 120 px
 *
 * Antes el footer pintaba el sello con una altura fija de 68px y ancho
 * automático. Con el archivo vertical del cliente (480×758) eso lo dejaba
 * renderizado a 33×52 px — medido en el navegador — o sea la quinta parte de
 * lo que exige el manual, con las cuatro líneas de texto ilegibles.
 *
 * Acá se mide el archivo REAL y se devuelve la caja que le corresponde, en
 * vez de suponer una orientación: el cliente puede subir mañana la horizontal
 * desde Configuración y el footer tiene que respetarla igual.
 *
 * El getimagesize() se cachea por ruta+mtime: sin eso sería una lectura de
 * disco en el footer de TODAS las páginas del sitio.
 */
class RegistrySeal
{
    /** Vertical: la que el manual publica a 150×250. */
    private const VERTICAL = ['w' => 150, 'h' => 250];

    /** Horizontal: 300×120. */
    private const HORIZONTAL = ['w' => 300, 'h' => 120];

    /**
     * @return array{w:int,h:int,orientation:string}|null null si no hay sello
     *         o si el archivo no se puede medir (ahí el footer usa su caja
     *         por defecto y no se inventa un tamaño).
     */
    public static function box(?string $path): ?array
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('media');

        if (! $disk->exists($path)) {
            return null;
        }

        $key = 'registry_seal.box.'.md5($path.'|'.$disk->lastModified($path));

        return Cache::rememberForever($key, static function () use ($disk, $path): ?array {
            $size = @getimagesize($disk->path($path));

            if ($size === false || empty($size[0]) || empty($size[1])) {
                return null;
            }

            [$w, $h] = $size;

            // El corte va en 1.0: cuadrado o más alto que ancho se trata como
            // vertical, que es la presentación por defecto del manual.
            return $w > $h
                ? self::HORIZONTAL + ['orientation' => 'horizontal']
                : self::VERTICAL + ['orientation' => 'vertical'];
        });
    }
}
