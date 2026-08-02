<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `review_date` (2026-07-26) guarda el texto crudo del import de WordPress
 * ("03 Mar 2024", " 26 Marzo 2024"…) — perfecto para no perder el dato
 * original, pero imposible de ordenar/formatear de forma confiable para la
 * portada. Los testimonios cargados a mano desde Filament tampoco tenían
 * ningún campo de fecha propio (solo `created_at`, que es "cuándo se guardó
 * el registro en el CMS", no "cuándo pasó la reseña").
 *
 * `reviewed_at` es una fecha real (tipo DATE) pensada para mostrar en el
 * home: "Nombre · fecha · tour". El modelo la autocompleta a hoy si se crea
 * sin valor (ver Testimonial::booted()), así ningún testimonio nuevo queda
 * sin fecha mostrable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->date('reviewed_at')->nullable()->after('traveled_as');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });
    }
};
