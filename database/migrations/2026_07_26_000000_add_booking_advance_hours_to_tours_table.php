<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Anticipación mínima (en horas) para reservar el tour. Viene de
            // la meta `agendar` del WordPress importado (docs/data/IMPORT-TOURS-WP.md).
            // NULL = sin anticipación mínima informada.
            $table->unsignedInteger('booking_advance_hours')->nullable()->after('departure_time');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('booking_advance_hours');
        });
    }
};
