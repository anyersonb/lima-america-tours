<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guías/equipo reales del cliente (docs/proyecto/08-seo.md, hallazgo S-04:
 * el sitio no tenía ni una cara humana). La tabla arranca vacía a propósito
 * — nada de sembrar "Juan Pérez, guía experto" por seeder; el contenido lo
 * carga el cliente desde Filament con sus propias fotos y bios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role_es')->nullable();
            $table->string('role_en')->nullable();
            $table->string('role_pt')->nullable();
            $table->string('photo')->nullable();
            $table->text('bio_es')->nullable();
            $table->text('bio_en')->nullable();
            $table->text('bio_pt')->nullable();
            // Idiomas que habla (ej. ["Español", "Inglés", "Quechua"]). JSON en
            // vez de una tabla pivote: es una lista corta y sin necesidad de
            // reportar/filtrar por idioma en otro lugar del sistema.
            $table->json('languages')->nullable();
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
