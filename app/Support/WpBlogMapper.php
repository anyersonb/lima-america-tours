<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Pure, testable mapping rules for the WordPress blog export
 * (limaamericatours.com, storage/app/wp-import/blog.json) to `blog_posts`
 * fields. Mirrors the role of App\Support\WpTourMapper for tours: no DB, no
 * filesystem I/O, just data transforms so they can be unit tested in
 * isolation from the import command.
 */
class WpBlogMapper
{
    /**
     * Resolves the display author name. WordPress admin/service accounts
     * (e.g. "limatours.adm") are replaced with the brand name so they never
     * show up as the "author" of a public post.
     */
    public static function authorName(array $author): string
    {
        $name = trim((string) ($author['display_name'] ?? ''));

        if ($name === '' || Str::contains(Str::lower($name), ['.adm', 'admin'])) {
            return 'Lima América Tours';
        }

        return $name;
    }

    /**
     * Parses the AIOSEO tag/keyword meta fields. WordPress stores them as a
     * PHP-serialized array (often the literal empty array "a:0:{}") or,
     * occasionally, as a plain comma-separated string. Returns null when
     * there is nothing usable, so the caller can store NULL instead of an
     * empty array.
     */
    public static function parseTags(?string $raw): ?array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^a:\d+:\{/', $raw)) {
            $unserialized = @unserialize($raw);
            if (! is_array($unserialized)) {
                return null;
            }
            $tags = self::cleanList($unserialized);

            return $tags ?: null;
        }

        $tags = self::cleanList(explode(',', $raw));

        return $tags ?: null;
    }

    /** Trims every item, drops empty ones and reindexes the array. */
    private static function cleanList(array $items): array
    {
        return array_values(array_filter(
            array_map(fn ($t) => trim((string) $t), $items),
            fn ($t) => $t !== ''
        ));
    }

    /**
     * Estimated reading time at ~200 words per minute, rounded up, with a
     * floor of 1 minute for any non-empty body. Null when the body has no
     * actual text (so the column can stay NULL instead of showing "0 min").
     */
    public static function readingMinutes(?string $bodyHtml): ?int
    {
        $text = trim(strip_tags((string) $bodyHtml));
        if ($text === '') {
            return null;
        }

        return max(1, (int) ceil(str_word_count($text) / 200));
    }

    /**
     * Excerpt: prefers the JetEngine "titulo-1" subtitle (short, human
     * written); falls back to a stripped and truncated version of the body
     * so the column is never left empty.
     */
    public static function excerpt(?string $titulo1, string $texto1Html): string
    {
        $titulo1 = trim((string) $titulo1);
        if ($titulo1 !== '') {
            return $titulo1;
        }

        return Str::limit(trim(strip_tags($texto1Html)), 200);
    }

    /**
     * Builds the final HTML body. JetEngine's second block ("titulo-2" /
     * "texto-2") is almost always empty in this export, but when it does
     * carry content it gets appended below the main block, with its title
     * rendered as an <h2> heading.
     */
    public static function buildBody(string $texto1, ?string $titulo2 = null, ?string $texto2 = null): string
    {
        $body = trim($texto1);
        $titulo2 = trim((string) $titulo2);
        $texto2 = trim((string) $texto2);

        if ($texto2 !== '') {
            if ($titulo2 !== '') {
                $body .= "\n<h2>".e($titulo2).'</h2>';
            }
            $body .= "\n".$texto2;
        }

        return $body;
    }
}
