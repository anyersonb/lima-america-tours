<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diapositivas administrables del slider del hero de la home (mockup
 * `01-home.jpeg`: 4 puntos + flechas ←→). Antes solo existía el Setting
 * `home_hero_image` (una sola foto). La tabla arranca vacía a propósito —
 * `HeroSlideSeeder` siembra UNA fila con la imagen actual del hero para que
 * una instalación nueva se vea igual, pero el resto de diapositivas las
 * carga el cliente desde Filament.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            // Ruta relativa en el disco "media" (mismo disco/directorio "home"
            // que home_hero_image), lista para pasar por ResponsiveImage.
            $table->string('image');
            $table->string('alt_es');
            $table->string('alt_en')->nullable();
            $table->string('alt_pt')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
