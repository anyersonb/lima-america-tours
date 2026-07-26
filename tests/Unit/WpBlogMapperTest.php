<?php

namespace Tests\Unit;

use App\Support\WpBlogMapper;
use PHPUnit\Framework\TestCase;

/**
 * Pure mapping rules for the WordPress blog export (limaamericatours.com,
 * storage/app/wp-import/blog.json) to `blog_posts` fields. No DB, no I/O.
 */
class WpBlogMapperTest extends TestCase
{
    // ── authorName ──────────────────────────────────────────────────────────

    public function test_author_name_replaces_admin_looking_accounts_with_brand_name(): void
    {
        $this->assertSame('Lima América Tours', WpBlogMapper::authorName(['display_name' => 'limatours.adm']));
        $this->assertSame('Lima América Tours', WpBlogMapper::authorName(['display_name' => 'admin']));
        $this->assertSame('Lima América Tours', WpBlogMapper::authorName(['display_name' => 'Administrator']));
    }

    public function test_author_name_keeps_a_real_display_name(): void
    {
        $this->assertSame('Karla Ríos', WpBlogMapper::authorName(['display_name' => 'Karla Ríos']));
    }

    public function test_author_name_falls_back_to_brand_when_missing(): void
    {
        $this->assertSame('Lima América Tours', WpBlogMapper::authorName([]));
        $this->assertSame('Lima América Tours', WpBlogMapper::authorName(['display_name' => '']));
    }

    // ── parseTags ────────────────────────────────────────────────────────────

    public function test_parse_tags_returns_null_for_empty_or_serialized_empty_array(): void
    {
        $this->assertNull(WpBlogMapper::parseTags(null));
        $this->assertNull(WpBlogMapper::parseTags(''));
        $this->assertNull(WpBlogMapper::parseTags('a:0:{}'));
    }

    public function test_parse_tags_unserializes_a_php_serialized_array(): void
    {
        $serialized = serialize(['lima', 'gastronomia', '']);
        $this->assertSame(['lima', 'gastronomia'], WpBlogMapper::parseTags($serialized));
    }

    public function test_parse_tags_splits_a_plain_comma_separated_string(): void
    {
        $this->assertSame(['lima', 'ceviche'], WpBlogMapper::parseTags('lima, ceviche'));
    }

    // ── readingMinutes ───────────────────────────────────────────────────────

    public function test_reading_minutes_is_null_for_empty_body(): void
    {
        $this->assertNull(WpBlogMapper::readingMinutes(''));
        $this->assertNull(WpBlogMapper::readingMinutes(null));
        $this->assertNull(WpBlogMapper::readingMinutes('<p></p>'));
    }

    public function test_reading_minutes_counts_words_at_200wpm_with_a_minimum_of_one(): void
    {
        $shortBody = '<p>'.implode(' ', array_fill(0, 10, 'palabra')).'</p>';
        $this->assertSame(1, WpBlogMapper::readingMinutes($shortBody));

        $longBody = '<p>'.implode(' ', array_fill(0, 450, 'palabra')).'</p>';
        $this->assertSame(3, WpBlogMapper::readingMinutes($longBody)); // ceil(450/200) = 3
    }

    // ── excerpt ──────────────────────────────────────────────────────────────

    public function test_excerpt_prefers_titulo_1_when_present(): void
    {
        $this->assertSame(
            'Paso a paso del sabor más famoso del Perú',
            WpBlogMapper::excerpt('Paso a paso del sabor más famoso del Perú', '<p>Texto largo del cuerpo...</p>')
        );
    }

    public function test_excerpt_falls_back_to_stripped_and_limited_body(): void
    {
        $body = '<p>'.str_repeat('a', 300).'</p>';
        $excerpt = WpBlogMapper::excerpt('', $body);

        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertLessThanOrEqual(210, mb_strlen($excerpt)); // 200 + '...' margin
    }

    // ── buildBody ────────────────────────────────────────────────────────────

    public function test_build_body_returns_texto_1_alone_when_texto_2_is_empty(): void
    {
        $this->assertSame('<p>Solo bloque 1</p>', WpBlogMapper::buildBody('<p>Solo bloque 1</p>', '', ''));
        $this->assertSame('<p>Solo bloque 1</p>', WpBlogMapper::buildBody('<p>Solo bloque 1</p>', null, null));
    }

    public function test_build_body_appends_texto_2_with_titulo_2_as_heading(): void
    {
        $body = WpBlogMapper::buildBody('<p>Bloque 1</p>', 'Segunda parte', '<p>Bloque 2</p>');

        $this->assertStringContainsString('<p>Bloque 1</p>', $body);
        $this->assertStringContainsString('<h2>Segunda parte</h2>', $body);
        $this->assertStringContainsString('<p>Bloque 2</p>', $body);
        $this->assertTrue(strpos($body, 'Bloque 1') < strpos($body, 'Segunda parte'));
    }

    public function test_build_body_appends_texto_2_without_heading_when_titulo_2_is_empty(): void
    {
        $body = WpBlogMapper::buildBody('<p>Bloque 1</p>', '', '<p>Bloque 2</p>');

        $this->assertStringNotContainsString('<h2>', $body);
        $this->assertStringContainsString('<p>Bloque 2</p>', $body);
    }
}
