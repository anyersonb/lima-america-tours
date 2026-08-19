<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * New fields for the blog-article mockup (docs/rebrand/inventario/02-tour-y-blog.md
     * §2.D, byline row): `author_role` ("Guía Local") and `author_photo`
     * (avatar). Deliberately simpler than the inventory's `guide_id` FK
     * proposal — the brief for this batch scoped it down to two nullable
     * columns on `blog_posts` itself, since none of the 10 published posts
     * has an individual author assigned yet and a FK to `guides` is a
     * bigger decision left for later. Both nullable: the byline component
     * must be able to hide itself when they're empty, exactly like
     * `author_name` already does.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('author_role')->nullable()->after('author_name');
            $table->string('author_photo')->nullable()->after('author_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['author_role', 'author_photo']);
        });
    }
};
