<?php

namespace App\Support;

/**
 * Pure, testable mapping rules for the WordPress reviews export
 * (limaamericatours.com, storage/app/wp-import/reviews.json) to
 * `testimonials` fields. These 13 reviews all belong to the free tour
 * (guide "Augusto") and are global (no tour_id). Mirrors the role of
 * App\Support\WpBlogMapper for blog posts: no DB, no filesystem I/O, just
 * data transforms so they can be unit tested in isolation from the import
 * command.
 */
class WpReviewMapper
{
    /**
     * Resolves the reviewer's display name.
     *
     * Data-entry mistake in the original export: WP record #861 has
     * meta.nombre === meta.procedencia ("Bogotá", the origin city typed
     * twice into the wrong field). The real name only survives in the WP
     * post title ("Aura"), so whenever `nombre` matches `procedencia`
     * (case/whitespace-insensitive) we prefer the post title instead.
     */
    public static function resolveName(array $meta, string $postTitle): string
    {
        $nombre = trim((string) ($meta['nombre'] ?? ''));
        $procedencia = trim((string) ($meta['procedencia'] ?? ''));

        if ($nombre !== '' && $procedencia !== '' && mb_strtolower($nombre) === mb_strtolower($procedencia)) {
            return trim($postTitle) ?: $nombre;
        }

        return $nombre !== '' ? $nombre : (trim($postTitle) ?: 'Anónimo');
    }

    /** Trims the origin city/country; blank or whitespace-only becomes null. */
    public static function cleanCountry(?string $procedencia): ?string
    {
        $value = trim((string) $procedencia);

        return $value !== '' ? $value : null;
    }

    /**
     * Trims the review text. WP exports keep stray trailing "\r\n" on a few
     * records (e.g. #856, #833); the content itself (language, typos) is
     * preserved as-is, no translation or "correction".
     */
    public static function cleanQuote(string $comentario): string
    {
        return trim($comentario);
    }

    public static function rating(?string $valoracion): int
    {
        $value = (int) trim((string) $valoracion);

        return $value > 0 ? $value : 5;
    }

    /** The WP `viajo-en` meta, trimmed; null when missing/blank. */
    public static function travelType(?string $viajoEn): ?string
    {
        $value = trim((string) $viajoEn);

        return $value !== '' ? $value : null;
    }

    /** The WP `fecha` meta kept verbatim (irregular formats, ES/EN); null when blank. */
    public static function reviewDate(?string $fecha): ?string
    {
        $value = trim((string) $fecha);

        return $value !== '' ? $value : null;
    }

    /**
     * Best-effort chronological key ("Y-m-d") parsed out of the irregular
     * `fecha` meta (e.g. "15 mar 2024", " 26 Marzo 2024", "24 Ene 2024") so
     * the importer can assign `order` by actual review date rather than by
     * WP id. Returns null when the string doesn't match the "d month Y"
     * shape — the caller falls back to WP id in that case. This is only
     * used for sorting; the original string is always stored verbatim in
     * `review_date` (see reviewDate()).
     */
    public static function sortableDate(?string $fecha): ?string
    {
        $value = trim((string) $fecha);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/(\d{1,2})\s+([^\s\d]+)\s+(\d{4})/u', $value, $m)) {
            return null;
        }

        $months = [
            'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
        ];
        $key = substr(self::deaccent(mb_strtolower($m[2])), 0, 3);
        if (! isset($months[$key])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $m[3], $months[$key], (int) $m[1]);
    }

    private static function deaccent(string $s): string
    {
        return strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }
}
