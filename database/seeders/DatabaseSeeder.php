<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@limaamericatours.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('LimaAmerica2026!'),
                'email_verified_at' => now(),
            ]
        );

        // Regiones
        //
        // hero_image: hasta 2026-08-10 apuntaba a assets/banners/Rectangle
        // 192{16,18,19}.jpg — placeholders de 205×123px estirados a ancho
        // completo en el grid de destinos (hallazgo del jefe revisando el
        // lote de mockups). Reemplazadas por fotos reales del propio
        // catálogo (disco "public", carpeta tours/), ya usadas como
        // portada de tours reales de cada región, todas ≥1900px de ancho.
        // El campo sigue siendo un FileUpload editable en Filament — esto
        // solo cambia el default de una instalación nueva.
        $regions = [
            ['slug' => 'lima', 'name_es' => 'Lima', 'name_en' => 'Lima', 'eyebrow_es' => 'EXPLORA LA CAPITAL', 'eyebrow_en' => 'EXPLORE THE CAPITAL', 'hero_image' => 'tours/2024-02-barranco-timeout.jpg', 'order' => 1],
            ['slug' => 'ica', 'name_es' => 'Ica', 'name_en' => 'Ica', 'eyebrow_es' => 'AVENTURA EN EL DESIERTO', 'eyebrow_en' => 'DESERT ADVENTURE', 'hero_image' => 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg', 'order' => 2],
            ['slug' => 'cusco', 'name_es' => 'Cusco', 'name_en' => 'Cusco', 'eyebrow_es' => 'CIUDADELA SAGRADA', 'eyebrow_en' => 'SACRED CITADEL', 'hero_image' => 'tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg', 'order' => 3],
        ];
        foreach ($regions as $r) {
            Region::firstOrCreate(['slug' => $r['slug']], $r);
        }

        // Categorías
        $categories = [
            ['slug' => 'cultural', 'name_es' => 'Tours Culturales', 'name_en' => 'Cultural Tours', 'order' => 1],
            ['slug' => 'aventura', 'name_es' => 'Tours de Aventura', 'name_en' => 'Adventure Tours', 'order' => 2],
            ['slug' => 'gastronomia', 'name_es' => 'Experiencias Culinarias', 'name_en' => 'Culinary Experiences', 'order' => 3],
            ['slug' => 'otros', 'name_es' => 'Otros', 'name_en' => 'Other', 'order' => 4],
        ];
        foreach ($categories as $c) {
            Category::firstOrCreate(['slug' => $c['slug']], $c);
        }

        $this->call([
            TourSeeder::class,
            TestimonialSeeder::class,
            OfferSeeder::class,
            SettingSeeder::class,
            // Registros Page de "nosotros"/"contacto": sin esto el listado de
            // Páginas del panel queda vacío y el cliente no puede editar el
            // copy migrado a PageResource (hallazgo CRO 2026-08-11).
            StaticPagesSeeder::class,
        ]);
    }
}
