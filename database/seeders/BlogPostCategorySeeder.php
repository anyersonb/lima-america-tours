<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Clasificación de los 12 posts del blog en categorías (2026-08-11).
 *
 * Hallazgo: solo 2 de 12 posts tenían `category` (columna string libre,
 * ver migración 2026_06_29_000010). Sin categoría, el mockup de Blog pierde
 * dos elementos centrales: el badge rojo sobre la portada y los filtros pill
 * ("Todos" + una pill por categoría), que `BlogController::index()` arma
 * con un `distinct()` sobre esta misma columna.
 *
 * Esto es curaduría de contenido propio, no un hecho del negocio inventado:
 * clasificamos posts que ya existen según su propio título/tema. Se usa un
 * conjunto corto de 5 categorías (Gastronomía, Destinos, Lima, Cultura,
 * Consejos) para 12 posts en vez de una categoría casi por post — se
 * fusionaron a propósito "Barranco y Miraflores" y "Museo Larco" bajo una
 * sola "Cultura" (ambas son contenido cultural/histórico de Lima) en vez de
 * dejar "Arte y cultura" y "Cultura" como dos cubetas de 1 post cada una.
 *
 * Es una propuesta editorial, no una verdad grabada en piedra: el campo
 * sigue siendo un TextInput libre en Filament → Blog → (post) → Categoría,
 * así que el cliente puede renombrar o reclasificar cualquier post sin
 * tocar código.
 *
 * Idempotente en el sentido de "no duplica filas" (actualiza por `slug`,
 * nunca crea). No está pensado para correr en un cron: si el cliente ya
 * cambió la categoría de un post desde el panel, volver a correr este
 * seeder SÍ la pisa — es un backfill de una sola vez, igual que
 * GuideSeeder/TourCoverImageFixSeeder en este mismo lote.
 */
class BlogPostCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        // Gastronomía
        'como-se-prepara-el-ceviche-peruano' => 'Gastronomía',
        'mejores-restaurantes-en-lima-guia-gastronomica-para-viajeros' => 'Gastronomía',
        'los-mejores-restaurantes-en-lima' => 'Gastronomía',
        'desayuno-bueno-bonito-y-barato' => 'Gastronomía',

        // Destinos
        'huacachina-el-oasis-imperdible-de-ica' => 'Destinos',
        'huacachina-el-oasis-imperdible-de-ica-aventura-y-encanto-con-lima-america-tours' => 'Destinos',

        // Lima (free tours / orientación en la ciudad)
        'free-tours-en-lima-la-mejor-forma-de-conocer-la-ciudad' => 'Lima',
        'free-tours-en-lima-la-mejor-forma-de-conocer-la-ciudad-con-lima-america-tours' => 'Lima',

        // Cultura (barrios bohemios + museo)
        'que-hacer-en-barranco-y-miraflores' => 'Cultura',
        'que-hacer-en-barranco-lima' => 'Cultura',
        'visitemos-el-museo-larco-en-lima' => 'Cultura',

        // Consejos prácticos
        'que-transporte-tomar-en-tu-visita-a-lima' => 'Consejos',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $slug => $category) {
            BlogPost::where('slug', $slug)->update(['category' => $category]);
        }
    }
}
