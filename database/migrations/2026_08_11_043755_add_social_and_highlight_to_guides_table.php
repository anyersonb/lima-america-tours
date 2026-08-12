<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mockup 02-nosotros ("Guías locales, amigos y amantes de nuestra cultura")
 * muestra un ícono de Instagram y uno de WhatsApp por tarjeta de guía, y en
 * el caso de Samira un dato de valor verificable (mencionada por nombre en
 * 15 de 20 reseñas de Google/TripAdvisor, docs/rebrand/CONTENIDO-REAL-PRODUCCION.md
 * §3.2). Ninguno de los dos existía en la tabla. Ambos nullable: sin el dato
 * real, el ícono/badge correspondiente simplemente no se pinta (mismo
 * criterio "sin dato, se oculta" del resto del proyecto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->string('instagram_handle')->nullable()->after('languages');
            $table->string('whatsapp_number')->nullable()->after('instagram_handle');
            $table->string('highlight_es')->nullable()->after('bio_pt');
            $table->string('highlight_en')->nullable()->after('highlight_es');
            $table->string('highlight_pt')->nullable()->after('highlight_en');
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropColumn(['instagram_handle', 'whatsapp_number', 'highlight_es', 'highlight_en', 'highlight_pt']);
        });
    }
};
