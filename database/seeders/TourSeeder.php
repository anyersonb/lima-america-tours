<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Region;
use App\Models\Tour;
use Illuminate\Database\Seeder;

class TourSeeder extends Seeder
{
    public function run(): void
    {
        $regions = Region::pluck('id', 'slug');
        $cats = Category::pluck('id', 'slug');

        $tours = [
            [
                'slug' => 'huacachina-paracas-full-day',
                'region_id' => $regions['ica'] ?? null,
                'category_id' => $cats['aventura'] ?? null,
                'title_es' => 'Tour de día Completo al Oasis de Huacachina + Islas Ballestas en Paracas',
                'title_en' => 'Full Day Tour to Huacachina Oasis + Ballestas Islands in Paracas',
                'subtitle_es' => 'Aventura, naturaleza y desierto en una sola jornada',
                'description_es' => 'Disfruta de un día completo combinando la magia del oasis de Huacachina con la riqueza natural de las Islas Ballestas en Paracas. Una experiencia diseñada para viajeros que buscan diversidad de paisajes en una sola jornada.',
                'description_en' => 'Enjoy a full day combining the magic of Huacachina oasis with the natural wealth of the Ballestas Islands. An experience designed for travelers seeking diverse landscapes in a single day.',
                'price' => 100,
                'price_before' => 125,
                'duration' => 'Full Day',
                'departure_time' => '05:00 AM',
                'return_time' => '10:30 PM',
                'max_capacity' => 20,
                // 2026-08-11: portada/galería reemplazadas — apuntaban al
                // placeholder del kit de mockup (205×123px reales, estirado a
                // ancho completo). Fotos reales del propio catálogo
                // (storage/app/public/tours), del oasis de Huacachina y las
                // Islas Ballestas en Paracas, todas ≥900px de ancho.
                'cover_image' => 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg',
                'gallery' => ['tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg', 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-7-scaled-1.jpg', 'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-2.jpg', 'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-6.jpg', 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-11.jpg'],
                'badge_text' => 'CUPOS LIMITADOS',
                'badge_type' => 'warn',
                'rating' => 4.6,
                'reviews_count' => 30,
                'is_featured' => true,
                'is_published' => true,
                'order' => 1,
                'itinerary_es' => [
                    ['time' => '05:00 AM', 'title' => 'Recojo del hotel', 'description' => 'Pasamos a recogerte por tu hotel en Lima en una unidad cómoda y climatizada.'],
                    ['time' => '07:30 AM', 'title' => 'Llegada a Paracas', 'description' => 'Desayuno breve y briefing del recorrido por las Islas Ballestas.'],
                    ['time' => '08:30 AM', 'title' => 'Islas Ballestas', 'description' => 'Embarcamos para observar lobos marinos, pingüinos y aves guaneras.'],
                    ['time' => '11:00 AM', 'title' => 'Reserva de Paracas', 'description' => 'Recorrido panorámico: Catedral, Playa Roja y mirador del Cóndor.'],
                    ['time' => '01:30 PM', 'title' => 'Almuerzo en Ica', 'description' => 'Almuerzo típico (no incluido).'],
                    ['time' => '03:00 PM', 'title' => 'Oasis de Huacachina', 'description' => 'Tiempo libre y opcional de tubulares + sandboarding.'],
                    ['time' => '06:30 PM', 'title' => 'Retorno a Lima', 'description' => 'Salida con dejada en hotel céntrico.'],
                ],
                'includes_es' => ['Recojo y retorno al hotel', 'Transporte turístico climatizado', 'Guía oficial bilingüe', 'Embarque a Islas Ballestas', 'Ingreso a Reserva de Paracas'],
                'excludes_es' => ['Almuerzo en Ica', 'Tubulares en Huacachina', 'Bebidas adicionales', 'Propinas'],
                'recommendations_es' => 'Llevar protector solar SPF50+, lentes de sol, sombrero, ropa cómoda, cámara, agua y documento de identidad.',
                'notes_es' => 'El recorrido marítimo puede sufrir cambios por condiciones climáticas. En caso de cancelación se reembolsa o reagenda.',
                'seo_title' => 'Tour Huacachina + Islas Ballestas desde Lima | Lima América Tours',
                'seo_description' => 'Reserva el tour Full Day Huacachina + Islas Ballestas. Desde Lima, con guía oficial, transporte y mejor precio.',
            ],
            [
                'slug' => 'lima-ancestral-colonial',
                'region_id' => $regions['lima'] ?? null,
                'category_id' => $cats['cultural'] ?? null,
                'title_es' => 'Full Day Lima Ancestral, Colonial y Moderna',
                'title_en' => 'Full Day Ancient, Colonial and Modern Lima',
                'description_es' => 'Recorre las tres caras de Lima: la milenaria Pachacamac, el Centro Histórico colonial y los modernos distritos de Miraflores y Barranco.',
                'price' => 100,
                'price_before' => 125,
                'duration' => 'Full Day',
                // 2026-08-11: ídem — fotos reales del Full Day Lima Ancestral
                // (Centro Histórico), todas ≥788px de ancho.
                'cover_image' => 'tours/FULL-DAY-LIMA-ANCESTRAL-5-1-1.jpg',
                'gallery' => ['tours/FULL-DAY-LIMA-ANCESTRAL-5-1-1.jpg', 'tours/FULL-DAY-LIMA-ANCESTRAL-4-1.jpg', 'tours/FULL-DAY-LIMA-ANCESTRAL-6-1.jpg', 'tours/FULL-DAY-LIMA-ANCESTRAL-7-1.jpg', 'tours/CENTRO-HISTORICO-DE-LIMA-PARQUE-DE-LAS-AGUAS-4.jpg'],
                'badge_text' => '5 CUPOS DE 20',
                'badge_type' => 'error',
                'rating' => 4.8,
                'reviews_count' => 28,
                'is_featured' => true,
                'order' => 2,
            ],
            [
                'slug' => 'nazca-huacachina-2-dias',
                'region_id' => $regions['ica'] ?? null,
                'category_id' => $cats['aventura'] ?? null,
                'title_es' => 'Las Enigmáticas Líneas de Nazca + Oasis de Huacachina e Islas Ballestas',
                'title_en' => 'Mysterious Nazca Lines + Huacachina Oasis & Ballestas Islands',
                'description_es' => 'Dos días para descubrir los misterios del Perú: las enigmáticas Líneas de Nazca, el oasis de Huacachina y la fauna marina de Paracas.',
                'price' => 220,
                'price_before' => 250,
                'duration' => '2 Días',
                // 2026-08-11: ídem — Líneas de Nazca + Huacachina/Ballestas,
                // sin repetir ningún archivo ya usado como portada de otro
                // tour publicado (tours/2024-02-Nazca-02.webp es la portada
                // real del tour #9 "Full day Nazca e Islas Ballestas desde
                // Lima" y se evitó a propósito).
                'cover_image' => 'tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5-1.jpg',
                'gallery' => ['tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5-1.jpg', 'tours/2024-02-Nazca-03.webp', 'tours/2024-02-Nazca-04.webp', 'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-3.jpg', 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-8-scaled-1.jpg'],
                'badge_text' => 'MÁS RESERVADO',
                'badge_type' => 'success',
                'rating' => 4.6,
                'reviews_count' => 30,
                'is_featured' => true,
                'order' => 3,
            ],
            [
                'slug' => 'nazca-full-day',
                'region_id' => $regions['ica'] ?? null,
                'category_id' => $cats['aventura'] ?? null,
                'title_es' => 'Full day a las Líneas de Nazca',
                'title_en' => 'Full day to the Nazca Lines',
                'description_es' => 'Sobrevuela las enigmáticas Líneas de Nazca en avioneta privada y descubre uno de los mayores misterios de la humanidad.',
                'price' => 300,
                'price_before' => 350,
                'duration' => 'Full Day',
                // 2026-08-11: ídem — Líneas de Nazca (sobrevuelo).
                'cover_image' => 'tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5.jpg',
                'gallery' => ['tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5.jpg', 'tours/2024-02-Nazca-05.webp', 'tours/2024-02-Nazca-06.webp', 'tours/2024-02-Nazca-09.webp'],
                'badge_text' => 'CUPOS LIMITADOS',
                'badge_type' => 'warn',
                'rating' => 4.8,
                'reviews_count' => 28,
                'order' => 4,
            ],
            [
                'slug' => 'city-tour-lima-catacumbas',
                'region_id' => $regions['lima'] ?? null,
                'category_id' => $cats['cultural'] ?? null,
                'title_es' => 'City Tour Lima + Catacumbas y Centro Histórico',
                'title_en' => 'Lima City Tour + Catacombs and Historic Center',
                'description_es' => 'Conoce las plazas, catedrales y catacumbas del Centro Histórico de Lima, declarado Patrimonio de la Humanidad.',
                'price' => 65,
                'price_before' => 80,
                'duration' => '4 horas',
                // 2026-08-11: no estaba en la lista de 5 reportada por el
                // jefe (en la BD viva este cover ya estaba vacío, alguien lo
                // limpió a mano desde el panel), pero el seeder — la fuente
                // que corre en cada instalación nueva/CI — seguía apuntando
                // al mismo placeholder. Se corrige aquí también para que un
                // fresh install no lo reintroduzca.
                'cover_image' => 'tours/2024-02-Centro-Historico-Lima-08.webp',
                'gallery' => ['tours/2024-02-Centro-Historico-Lima-08.webp', 'tours/2024-02-Centro-Historico-Lima-09.webp', 'tours/2024-02-Centro-Historico-Lima-11.webp', 'tours/CENTRO-HISTORICO-DE-LIMA-PARQUE-DE-LAS-AGUAS-7.jpg'],
                'badge_text' => 'NUEVO TOUR',
                'badge_type' => 'success',
                'rating' => 4.7,
                'reviews_count' => 18,
                'order' => 5,
            ],
            [
                'slug' => 'machu-picchu-full-day',
                'region_id' => $regions['cusco'] ?? null,
                'category_id' => $cats['cultural'] ?? null,
                'title_es' => 'Machu Picchu Full Day + Tren Panorámico desde Cusco',
                'title_en' => 'Full Day Machu Picchu + Panoramic Train from Cusco',
                'description_es' => 'Visita la maravilla del mundo Machu Picchu en tren panorámico desde Cusco. Una experiencia única e inolvidable.',
                'price' => 420,
                'price_before' => 480,
                'duration' => 'Full Day',
                // 2026-08-11: ídem — Machu Picchu. Reusa la misma foto real
                // de máxima resolución (2560×1707) que ya sirve de
                // hero_image de la región Cusco: no es un duplicado
                // accidental, es la mejor toma de Machu Picchu del catálogo.
                'cover_image' => 'tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg',
                'gallery' => ['tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg', 'tours/MACHU-2-DIAS-1.jpg', 'tours/2024-12-MACHU-2-DIAS-1-qulwpkgkbgqlczcpqjlzb39p7o3kkaw977pkcfyxdc.jpg', 'tours/2024-12-MACHU-2-DIAS-2-qulwpgp7k4lg2ji6chzh147uu4m3pihbup3mfc4i28.jpg', 'tours/pueblo-machu-picchu.jpg'],
                'badge_text' => 'EXPERIENCIA TOP',
                'badge_type' => 'success',
                'rating' => 4.9,
                'reviews_count' => 65,
                'is_featured' => true,
                'order' => 6,
            ],
        ];

        foreach ($tours as $t) {
            Tour::updateOrCreate(['slug' => $t['slug']], $t);
        }
    }
}
