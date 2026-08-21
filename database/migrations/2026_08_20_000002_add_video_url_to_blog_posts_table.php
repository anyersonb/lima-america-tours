<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Botón de play sobre el hero del artículo (docs/rebrand/inventario/spec-03-blog.md
     * §1, §9). Mismo patrón exacto que `tours.video_url`
     * (2026_08_19_000001_add_video_and_route_fields_to_tours_table): se
     * guarda la URL "de compartir" tal cual la pega el cliente y
     * `App\Support\VideoEmbed::normalize()` la convierte a la forma
     * incrustable en el accesor del modelo. Nullable: sin URL, el botón de
     * play no se muestra.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('guide_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
