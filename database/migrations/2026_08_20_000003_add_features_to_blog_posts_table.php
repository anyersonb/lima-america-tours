<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tarjeta blanca de 4 features que se monta sobre el hero del artículo
     * (docs/rebrand/inventario/spec-03-blog.md §1, §2, §9). JSON en vez de
     * una tabla propia: es una lista corta (0-4 filas) que vive y se edita
     * junto al post, sin necesidad de reportar/filtrar por feature en otro
     * lugar del sistema — mismo criterio ya usado para `guides.languages` y
     * `tours.comparison`. Se edita en Filament con un Repeater
     * (maxItems 4). El accesor `BlogPost::getFeatureCardsAttribute()` filtra
     * las filas sin título y nunca asume que haya 4: la tarjeta debe verse
     * bien con 0, 2, 3 o 4 bloques.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('features')->nullable()->after('video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
