<?php

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    /**
     * Las 3 imágenes venían del kit de placeholders de 205×123px reales
     * estirados a ~444px de ancho renderizado en las tarjetas "PROMOCIÓN"
     * encimadas al hero del Home (ver docs/rebrand/LOTE-MOCKUPS-AGO-2026.md,
     * "Problema transversal: el kit de imágenes está degradado"). Reemplazadas
     * por fotos reales del catálogo (storage/app/public/tours), todas con
     * ancho intrínseco ≥1900px — muy por encima del renderizado. Arreglado
     * aquí (fuente del seeder) y no solo en la fila ya sembrada: si alguien
     * corre `db:seed --class=OfferSeeder` de nuevo, `updateOrCreate` habría
     * vuelto a pisar el campo `image` con el placeholder viejo.
     *
     * 2026-08-12 — `price` pasó de `200` a `null` en las tres. Ese 200 era del
     * seeder y salía publicado como "Desde $200" en las TRES tarjetas a la vez,
     * incluidas una que es un descuento porcentual y un combo, que no tienen un
     * precio propio que sostener. Un precio inventado en pantalla es un defecto,
     * no un pendiente de configuración: si la clienta quiere mostrar un "desde",
     * lo carga por tarjeta en el panel y el bloque aparece solo (el Blade ya
     * envuelve el precio en `@if ($offer->price)`). Lo vigila
     * OfferPriceIsNotSeededTest.
     */
    public function run(): void
    {
        $offers = [
            [
                'title_es' => '10% de descuento haciendo tu reserva anticipada',
                'title_en' => '10% off booking in advance',
                'description_es' => 'Reserva con 30 días de anticipación y obtén un 10% de descuento en cualquier tour del catálogo.',
                'price' => null, // sin precio propio: el bloque "Desde" no se pinta (ver nota arriba)
                'image' => 'tours/FULL-DAY-LIMA-ANCESTRAL-5-1-1.jpg',
                'cta_label_es' => 'Leer más',
                'cta_url' => '/es/tours',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'title_es' => 'Tours grupales con tarifas especiales',
                'title_en' => 'Group tours with special rates',
                'description_es' => 'Para grupos de 6 o más personas, descuentos progresivos según el tamaño del grupo.',
                'price' => null, // sin precio propio: el bloque "Desde" no se pinta (ver nota arriba)
                'image' => 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg',
                'cta_label_es' => 'Leer más',
                'cta_url' => '/es/tours',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'title_es' => 'Combo Lima + Cusco con vuelo incluido',
                'title_en' => 'Lima + Cusco combo with flight included',
                'description_es' => 'Paquete completo de 5 días/4 noches con vuelo doméstico, hospedaje y tours.',
                'price' => null, // sin precio propio: el bloque "Desde" no se pinta (ver nota arriba)
                'image' => 'tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg',
                'cta_label_es' => 'Leer más',
                'cta_url' => '/es/tours',
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($offers as $o) {
            Offer::updateOrCreate(['title_es' => $o['title_es']], $o);
        }
    }
}
