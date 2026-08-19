<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * New fields for the tour-detail mockup (docs/rebrand/inventario/02-tour-y-blog.md
     * §1.D): the "Ver video" button, the "Dificultad" item in the dark data
     * bar, and the static "Mapa del recorrido" image. All nullable — none of
     * these ships with an invented default, so a tour without the data
     * simply hides the corresponding UI piece instead of publishing a
     * placeholder value.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('booking_advance_hours');
            $table->string('difficulty')->nullable()->after('video_url');
            $table->string('route_map_image')->nullable()->after('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'difficulty', 'route_map_image']);
        });
    }
};
