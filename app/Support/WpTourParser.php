<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Convierte los campos HTML del WordPress/JetEngine de limaamericatours.com
 * (exportados a storage/app/wp-import/tours.json) al formato que espera la
 * tabla `tours` de la app: texto plano con saltos de línea e itinerario /
 * incluye-excluye como arrays. El front (resources/views/tours/show.blade.php)
 * pinta estos campos escapados ({{ }} y explode("\n")), por eso NO se conserva
 * HTML: se degrada a texto limpio.
 */
class WpTourParser
{
    /** HTML de párrafos/listas -> texto plano con saltos de línea. */
    public static function htmlToText(?string $html): string
    {
        if (! $html) {
            return '';
        }

        // Cierres de bloque -> salto de línea antes de quitar etiquetas.
        $t = preg_replace('#</(p|div|li|h[1-6])>#i', "\n", $html);
        $t = preg_replace('#<br\s*/?>#i', "\n", $t);
        $t = strip_tags($t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normaliza espacios (incluye &nbsp; y zero-width) y colapsa saltos.
        $t = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $t);
        $t = preg_replace('/[ \t]+/', ' ', $t);
        $t = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $t);
        $lines = array_map('trim', explode("\n", $t));
        $t = implode("\n", $lines);

        return trim($t);
    }

    /**
     * Itinerario JetEngine: bloques <p><strong>HORA:</strong> Título</p>
     * seguidos de párrafos de descripción. Devuelve [{time,title,description}].
     * Si no hay patrón con <strong>, cae a un paso por párrafo/ítem.
     */
    public static function parseItinerary(?string $html): array
    {
        if (! $html || trim(strip_tags($html)) === '') {
            return [];
        }

        // Extrae bloques <p> y <li> en orden.
        preg_match_all('#<(p|li)\b[^>]*>(.*?)</\1>#is', $html, $m, PREG_SET_ORDER);
        $blocks = array_map(fn ($x) => $x[2], $m);

        // Fallback: sin bloques, parte por saltos del texto plano.
        if (empty($blocks)) {
            $blocks = array_filter(array_map('trim', explode("\n", self::htmlToText($html))));
        }

        $steps = [];
        $current = null;
        $hasStrongPattern = false;

        foreach ($blocks as $block) {
            // ¿Empieza con <strong>...</strong> (la "hora/etiqueta" del paso)?
            if (preg_match('#<strong>(.*?)</strong>(.*)#is', $block, $sm)) {
                $hasStrongPattern = true;
                if ($current) {
                    $steps[] = self::finishStep($current);
                }
                $label = self::dec(strip_tags($sm[1]));
                $rest = self::dec(strip_tags($sm[2]));
                // "3:00 AM:" -> time; el resto -> title. Si la etiqueta no parece
                // hora, se usa como título.
                $label = rtrim($label, ": \t");
                $isTime = (bool) preg_match('/\d/', $label) && Str::length($label) <= 20;
                $current = [
                    'time' => $isTime ? $label : '',
                    'title' => $isTime ? $rest : trim($label.' '.$rest),
                    'desc' => [],
                ];
            } else {
                $text = self::dec(strip_tags($block));
                if ($text === '') {
                    continue;
                }
                if ($current) {
                    $current['desc'][] = $text;
                } else {
                    // Párrafo suelto antes de cualquier hora: paso sin hora.
                    $current = ['time' => '', 'title' => $text, 'desc' => []];
                }
            }
        }
        if ($current) {
            $steps[] = self::finishStep($current);
        }

        // Sin patrón de horas: cada bloque es un paso-título simple.
        if (! $hasStrongPattern) {
            $steps = array_values(array_filter(array_map(function ($b) {
                $text = self::dec(strip_tags($b));

                return $text === '' ? null : ['time' => '', 'title' => $text, 'description' => ''];
            }, $blocks)));
        }

        return $steps;
    }

    /** Decodifica entidades, normaliza &nbsp;/zero-width y recorta. */
    private static function dec(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $s);

        return trim(preg_replace('/[ \t]+/', ' ', $s));
    }

    private static function finishStep(array $s): array
    {
        return [
            'time' => $s['time'],
            'title' => $s['title'],
            'description' => trim(implode("\n", $s['desc'])),
        ];
    }

    /**
     * El campo "que-incluye" del WP mezcla lo que INCLUYE y un bloque
     * "No incluye:" con lo que NO. Devuelve ['includes'=>[], 'excludes'=>[]].
     */
    public static function splitIncludesExcludes(?string $html): array
    {
        if (! $html) {
            return ['includes' => [], 'excludes' => []];
        }

        // Punto de corte: primera aparición de "No incluye".
        $pos = false;
        if (preg_match('/no\s+incluye/i', $html, $mm, PREG_OFFSET_CAPTURE)) {
            $pos = $mm[0][1];
        }

        if ($pos !== false) {
            $incHtml = substr($html, 0, $pos);
            $excHtml = substr($html, $pos);
        } else {
            $incHtml = $html;
            $excHtml = '';
        }

        return [
            'includes' => self::listItems($incHtml),
            'excludes' => self::listItems($excHtml),
        ];
    }

    /** Extrae <li> como strings limpios; si no hay <li>, usa líneas. */
    private static function listItems(?string $html): array
    {
        if (! $html) {
            return [];
        }

        $items = [];
        if (preg_match_all('#<li\b[^>]*>(.*?)</li>#is', $html, $m)) {
            foreach ($m[1] as $li) {
                $t = self::dec(strip_tags($li));
                if ($t !== '') {
                    $items[] = $t;
                }
            }
        } else {
            foreach (explode("\n", self::htmlToText($html)) as $line) {
                $line = trim($line, "- \t");
                // Ignora el propio rótulo "No incluye".
                if ($line !== '' && ! preg_match('/^no\s+incluye/i', $line)) {
                    $items[] = $line;
                }
            }
        }

        return $items;
    }
}
