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
     * Itinerario JetEngine -> [{time,title,description}]. El HTML del WP no es
     * uniforme entre tours:
     *  - Unos usan <p><strong>3:00 AM:</strong> Título</p> + párrafos.
     *  - Otros (multi-día) usan <h4> "Día N" y <b> (no <strong>) para la hora,
     *    a veces partida en varios <b> seguidos.
     * Por eso se capturan TODOS los bloques de contenido (p, li, h1-6) y se
     * detecta la hora por patrón en el texto, no por la etiqueta. Objetivo:
     * cero pérdida de contenido.
     */
    public static function parseItinerary(?string $html): array
    {
        if (! $html || trim(strip_tags($html)) === '') {
            return [];
        }

        preg_match_all('#<(p|li|h[1-6])\b[^>]*>(.*?)</\1>#is', $html, $m, PREG_SET_ORDER);
        $blocks = [];
        foreach ($m as $x) {
            $blocks[] = ['tag' => Str::lower($x[1]), 'html' => $x[2]];
        }
        // Fallback: sin bloques reconocibles, parte por líneas del texto plano.
        if (empty($blocks)) {
            foreach (array_filter(array_map('trim', explode("\n", self::htmlToText($html)))) as $l) {
                $blocks[] = ['tag' => 'p', 'html' => $l];
            }
        }

        $steps = [];
        $cur = null;
        $flush = function () use (&$steps, &$cur) {
            if ($cur === null) {
                return;
            }
            $step = [
                'time' => trim($cur['time']),
                'title' => trim($cur['title']),
                'description' => trim(implode("\n", $cur['desc'])),
            ];
            if ($step['time'] !== '' || $step['title'] !== '' || $step['description'] !== '') {
                $steps[] = $step;
            }
            $cur = null;
        };

        foreach ($blocks as $b) {
            $text = self::dec(strip_tags($b['html']));
            if ($text === '') {
                continue;
            }

            // ¿Arranca con una hora? (3:00 AM / 8:00 am / 11:00) — venga en
            // <strong>, <b> o sin etiqueta.
            if (preg_match('/^\s*(\d{1,2}[:.]\d{2})\s*(a\.?\s*m\.?|p\.?\s*m\.?)?\s*[:\-\x{2013}]?\s*(.*)$/isu', $text, $mm)) {
                $flush();
                $ampm = strtoupper(preg_replace('/[.\s]/', '', $mm[2] ?? ''));
                $time = trim($mm[1].($ampm ? ' '.$ampm : ''));
                $rest = trim($mm[3]);
                // Separa título / descripción en el primer ":".
                if (str_contains($rest, ':')) {
                    [$title, $desc] = array_map('trim', explode(':', $rest, 2));
                } else {
                    $title = $rest;
                    $desc = '';
                }
                $cur = ['time' => $time, 'title' => $title, 'desc' => $desc !== '' ? [$desc] : []];
            } elseif (self::looksLikeLabel($b, $text)) {
                // Encabezado corto ("Día 1") o bloque en negrita corto sin hora.
                $flush();
                $cur = ['time' => '', 'title' => $text, 'desc' => []];
            } else {
                // Párrafo de descripción del paso actual (o intro sin paso).
                if ($cur) {
                    $cur['desc'][] = $text;
                } else {
                    $cur = ['time' => '', 'title' => '', 'desc' => [$text]];
                }
            }
        }
        $flush();

        return $steps;
    }

    /** ¿El bloque es un rótulo (encabezado corto o negrita corta sin hora)? */
    private static function looksLikeLabel(array $b, string $text): bool
    {
        $short = mb_strlen($text) <= 60;
        if ($short && preg_match('/^h[1-6]$/', $b['tag'])) {
            return true;
        }
        // Bloque que es enteramente <strong>/<b> y corto (p.ej. "<p><b>Día 2</b></p>").
        if ($short && preg_match('#^\s*(<(strong|b)>.*?</(strong|b)>\s*)+$#is', trim($b['html']))) {
            return true;
        }

        return false;
    }

    /** Decodifica entidades, normaliza &nbsp;/zero-width y recorta. */
    private static function dec(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $s);

        return trim(preg_replace('/[ \t]+/', ' ', $s));
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
