<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Support\ResponsiveImage;
use Illuminate\Database\Seeder;

/**
 * Siembra UNA diapositiva con la foto que el hero ya muestra hoy por
 * defecto (ResponsiveImage::DEFAULT_PHOTO — la misma panorámica de Machu
 * Picchu que usa el fallback de `home_hero_image` en home.blade.php), para
 * que una instalación nueva vea el hero exactamente igual sin tener que
 * cargar nada desde Filament.
 *
 * Idempotente por diseño: `updateOrCreate` con esa ruta de imagen como
 * llave — volver a correrlo nunca duplica la fila, y si el cliente ya
 * cargó diapositivas propias, esta sigue conviviendo con ellas por orden.
 */
class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        HeroSlide::updateOrCreate(
            ['image' => ResponsiveImage::DEFAULT_PHOTO],
            [
                'alt_es' => 'Ciudadela inca de Machu Picchu entre montañas y nubes, Cusco, Perú',
                'alt_en' => 'Inca citadel of Machu Picchu among mountains and clouds, Cusco, Peru',
                'alt_pt' => 'Cidadela inca de Machu Picchu entre montanhas e nuvens, Cusco, Peru',
                'order' => 0,
                'is_active' => true,
            ]
        );
    }
}
