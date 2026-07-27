<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive fields for `reviews:import-wp` (13 real reviews of the free tour,
 * guide "Augusto", exported from limaamericatours.com WordPress). No
 * existing column is touched.
 *
 * - external_ref: idempotency key ("wp_review_{wp_id}"), unique so a second
 *   import run can never duplicate a row.
 * - review_date: the WP `fecha` meta kept verbatim. Formats are irregular
 *   (ES/EN, inconsistent casing/spacing — e.g. "03 Mar 2024", " 26 Marzo
 *   2024") so it is stored as a plain string rather than forced into a
 *   `date` column that would either reject or silently mis-parse some rows.
 * - traveled_as: the WP `viajo-en` meta ("en Pareja" / "Solo" / "en Familia"
 *   / "en Grupo").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('external_ref')->nullable()->unique()->after('tour_id');
            $table->string('review_date')->nullable()->after('external_ref');
            $table->string('traveled_as')->nullable()->after('review_date');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['external_ref', 'review_date', 'traveled_as']);
        });
    }
};
