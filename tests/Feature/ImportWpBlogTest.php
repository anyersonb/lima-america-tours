<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Runs `blog:import-wp` against the real WordPress export
 * (storage/app/wp-import/blog.json, 10 real posts from limaamericatours.com)
 * and its matching local images extract. Covers: creation count, HTML body
 * preserved, title mapping, cover image copied to the public disk, and
 * idempotency (running it twice must not duplicate rows).
 */
class ImportWpBlogTest extends TestCase
{
    use RefreshDatabase;

    private const KNOWN_SLUG = 'como-se-prepara-el-ceviche-peruano';

    protected function setUp(): void
    {
        parent::setUp();

        if (! File::exists(storage_path('app/wp-import/blog.json'))) {
            $this->markTestSkipped('storage/app/wp-import/blog.json not present locally.');
        }
    }

    public function test_import_creates_all_posts_from_the_export(): void
    {
        Artisan::call('blog:import-wp');

        $this->assertSame(10, BlogPost::count());
    }

    public function test_import_maps_title_and_keeps_html_body(): void
    {
        Artisan::call('blog:import-wp');

        $post = BlogPost::where('slug', self::KNOWN_SLUG)->firstOrFail();

        $this->assertSame('Cómo se prepara el ceviche peruano', $post->title_es);
        $this->assertNotEmpty($post->body_es);
        $this->assertStringContainsString('<p>', $post->body_es);
        $this->assertStringContainsString('ceviche', mb_strtolower($post->body_es));
    }

    public function test_import_copies_the_cover_image_to_the_public_disk(): void
    {
        Artisan::call('blog:import-wp');

        $post = BlogPost::where('slug', self::KNOWN_SLUG)->firstOrFail();

        $this->assertNotNull($post->cover_image);
        $this->assertStringStartsWith('blog/', $post->cover_image);
        $this->assertFileExists(storage_path('app/public/'.$post->cover_image));
    }

    public function test_import_is_idempotent(): void
    {
        Artisan::call('blog:import-wp');
        $this->assertSame(10, BlogPost::count());

        Artisan::call('blog:import-wp');
        $this->assertSame(10, BlogPost::count());

        // Re-running must not touch the slug uniqueness constraint nor
        // duplicate the known post.
        $this->assertSame(1, BlogPost::where('slug', self::KNOWN_SLUG)->count());
    }
}
