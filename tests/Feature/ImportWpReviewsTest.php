<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Runs `reviews:import-wp` against the real WordPress export
 * (storage/app/wp-import/reviews.json, 13 real reviews of the free tour,
 * guide "Augusto") and asserts they land in `testimonials` as global
 * (tour_id = null) rows. Mirrors the pattern of Tests\Feature\ImportWpBlogTest.
 */
class ImportWpReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! File::exists(storage_path('app/wp-import/reviews.json'))) {
            $this->markTestSkipped('storage/app/wp-import/reviews.json not present locally.');
        }
    }

    public function test_import_creates_all_reviews_as_global_testimonials(): void
    {
        Artisan::call('reviews:import-wp');

        $this->assertSame(13, Testimonial::whereNotNull('external_ref')->count());
        $this->assertSame(13, Testimonial::whereNotNull('external_ref')->whereNull('tour_id')->count());
        $this->assertSame(13, Testimonial::whereNotNull('external_ref')->where('rating', 5)->count());
        $this->assertSame(0, Testimonial::whereNotNull('external_ref')->whereNull('review_date')->count());
        $this->assertSame(0, Testimonial::whereNotNull('external_ref')->whereNull('traveled_as')->count());
    }

    public function test_import_sets_external_ref_from_the_wp_id(): void
    {
        Artisan::call('reviews:import-wp');

        $this->assertDatabaseHas('testimonials', [
            'external_ref' => 'wp_review_860',
            'name' => 'Victor',
        ]);
    }

    public function test_import_is_idempotent(): void
    {
        Artisan::call('reviews:import-wp');
        $this->assertSame(13, Testimonial::whereNotNull('external_ref')->count());

        Artisan::call('reviews:import-wp');
        $this->assertSame(13, Testimonial::whereNotNull('external_ref')->count());
        $this->assertSame(1, Testimonial::where('external_ref', 'wp_review_860')->count());
    }

    /**
     * Edge case: WP record #861 has meta.nombre === meta.procedencia ("Bogotá"),
     * a data-entry mistake in the original export. The real name lives in the
     * WP post title ("Aura"), so the importer must prefer it in that case.
     */
    public function test_import_uses_post_title_when_wp_name_matches_the_origin_city(): void
    {
        Artisan::call('reviews:import-wp');

        $review = Testimonial::where('external_ref', 'wp_review_861')->firstOrFail();

        $this->assertSame('Aura', $review->name);
        $this->assertSame('Bogotá', $review->country);
    }

    public function test_import_dry_run_does_not_write_to_the_database(): void
    {
        Artisan::call('reviews:import-wp', ['--dry-run' => true]);

        $this->assertSame(0, Testimonial::count());
    }
}
