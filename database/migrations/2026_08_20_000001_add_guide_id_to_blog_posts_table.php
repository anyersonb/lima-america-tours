<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Line firma real (docs/rebrand/inventario/spec-03-blog.md §5.1 / §9): FK
     * opcional a `guides`, el modelo que ya existe con foto/rol/bio para
     * Nosotros. Yesterday's `author_role`/`author_photo` columns (2026-08-19)
     * were a deliberately smaller stopgap because no post had a real author
     * yet; this FK is the "real" identity source once the client actually
     * assigns one. Nullable + `nullOnDelete()`: deleting a guide must not
     * cascade into deleting the article, it just falls back to the loose
     * author_* columns again (see BlogPost::getSignatureNameAttribute() and
     * siblings for the precedence rule between the four author sources).
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->foreignId('guide_id')
                ->nullable()
                ->after('author_photo')
                ->constrained('guides')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guide_id');
        });
    }
};
