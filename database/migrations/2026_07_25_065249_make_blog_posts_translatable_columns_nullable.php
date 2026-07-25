<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * BlogPost was crashing with a 500 ("Column 'title_en' cannot be null")
     * when an editor saved a post with only the Spanish tab filled in — the
     * EN/PT translatable columns were created NOT NULL with no default.
     * Made them nullable so the app-level fallback (BlogPost::boot()) is the
     * only thing responsible for filling them, instead of a hard DB error.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_en')->nullable()->change();
            $table->text('excerpt_en')->nullable()->change();
            $table->longText('body_en')->nullable()->change();

            $table->string('title_pt')->nullable()->change();
            $table->text('excerpt_pt')->nullable()->change();
            $table->longText('body_pt')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: rolling back only makes sense if every row already has EN/PT
     * content populated (either by an editor or by the model's fallback),
     * otherwise re-adding the NOT NULL constraint will fail.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_en')->nullable(false)->change();
            $table->text('excerpt_en')->nullable(false)->change();
            $table->longText('body_en')->nullable(false)->change();

            $table->string('title_pt')->nullable(false)->change();
            $table->text('excerpt_pt')->nullable(false)->change();
            $table->longText('body_pt')->nullable(false)->change();
        });
    }
};
