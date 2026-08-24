<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Bloque de reseñas de Tripadvisor del footer (logo + estrellas + rating +
 * cantidad de opiniones + posición, al estilo de la referencia que mandó el
 * jefe el 2026-08-21: "Tripadvisor · 4.9 · 3,968 reseñas · #1 en Lima").
 *
 * TODO O NADA a propósito. El bloque se pinta solo cuando están los TRES
 * datos que lo hacen verificable: enlace al perfil, rating y cantidad de
 * opiniones. Con dos de tres queda oculto, no a medias.
 *
 * El porqué: la cifra sin enlace es indistinguible de una inventada, y este
 * repo ya publicó un "4.8 (0 reseñas)" sembrado en los 24 tours y un "Desde
 * $200" del seeder (ver docs/rebrand/inventario/00-VALIDACION-STAGING.md). Un
 * default acá no sería un placeholder: sería una reseña falsa en el footer de
 * todas las páginas del sitio.
 *
 * La posición ("#1 en Lima") es el ÚNICO campo opcional: es un dato que
 * cambia solo, sin que nadie edite el sitio, así que se publica únicamente si
 * el cliente lo carga a mano y se puede vaciar sin tumbar el bloque.
 *
 * El enlace se reutiliza de `social_tripadvisor` (Configuración → Redes
 * sociales), que ya alimentaba las tarjetas de rating de cada tour: dos
 * campos para la misma URL terminan en dos URLs distintas.
 */
class TripadvisorBadge
{
    /**
     * @return array{url:string, rating:float, rating_label:string, count:int, count_label:string, rank:?string}|null
     */
    public static function data(string $locale = 'es'): ?array
    {
        $url = trim((string) Setting::get('social_tripadvisor', ''));
        if ($url === '') {
            return null;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://' . ltrim($url, '/');
        }

        $rating = (float) str_replace(',', '.', (string) Setting::get('tripadvisor_rating', ''));
        $count  = (int) preg_replace('/\D/', '', (string) Setting::get('tripadvisor_reviews_count', ''));

        // Rangos, no solo "no vacío": un rating de 0 o de 7 y un contador
        // negativo son datos mal cargados, y publicarlos rompe el bloque en
        // pantalla (las estrellas se calculan sobre 5).
        if ($rating <= 0 || $rating > 5 || $count <= 0) {
            return null;
        }

        $rank = trim((string) Setting::get("tripadvisor_rank_{$locale}", ''));
        if ($rank === '') {
            $rank = trim((string) Setting::get('tripadvisor_rank_es', ''));
        }

        return [
            'url' => $url,
            'rating' => $rating,
            // Una decimal, con el separador del idioma: "4.9" en inglés,
            // "4,9" en español y portugués.
            'rating_label' => number_format($rating, 1, $locale === 'en' ? '.' : ',', ''),
            'count' => $count,
            'count_label' => number_format($count, 0, '.', $locale === 'en' ? ',' : '.'),
            'rank' => $rank !== '' ? $rank : null,
        ];
    }
}
