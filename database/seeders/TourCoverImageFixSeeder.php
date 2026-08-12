<?php

namespace Database\Seeders;

use App\Models\Tour;
use Illuminate\Database\Seeder;

/**
 * Backfill de portada/galería para bases de datos sembradas ANTES del fix
 * del 2026-08-11 en TourSeeder (que sí corrige el problema para instalaciones
 * nuevas / CI). Este seeder es el que hay que correr una vez contra una BD
 * ya poblada (como `lima_america` en local) para que las filas existentes
 * queden igual que un fresh install.
 *
 * Hallazgo: `cover_image`/`gallery` de 6 tours (5 reportados por el jefe +
 * "City Tour Lima + Catacumbas", que en la BD viva ya tenía el campo vacío
 * pero seguía roto en el seeder) apuntaban a
 * assets/banners/Rectangle 192XX.jpg — 205×123px reales servidos estirados
 * a ~1900px. Medido con PDO directo (no Eloquent, no scopes) contra
 * `lima_america`:
 *
 *   SELECT COUNT(*) FROM tours WHERE cover_image LIKE '%Rectangle%'; -- 5
 *
 * Idempotente y de bajo riesgo por diseño:
 *  - Solo toca `cover_image` y `gallery`. Nunca `is_published`, `price`,
 *    `rating`, etc. — esos campos pueden haber cambiado desde el seed
 *    original por edición manual en Filament y no son parte de este fix.
 *  - Antes de escribir, verifica que el valor ACTUAL siga pareciendo el
 *    placeholder conocido (Rectangle / image.jpg / image-1.jpg). Si un
 *    admin ya subió una foto distinta desde el panel, correr esto de nuevo
 *    es un no-op para esa fila — no pisa su elección.
 */
class TourCoverImageFixSeeder extends Seeder
{
    /** slug => [cover_image, gallery] con las mismas rutas ya fijadas en TourSeeder. */
    private const FIXES = [
        'huacachina-paracas-full-day' => [
            'cover_image' => 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg',
            'gallery' => [
                'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg',
                'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-7-scaled-1.jpg',
                'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-2.jpg',
                'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-6.jpg',
                'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-11.jpg',
            ],
        ],
        'lima-ancestral-colonial' => [
            'cover_image' => 'tours/FULL-DAY-LIMA-ANCESTRAL-5-1-1.jpg',
            'gallery' => [
                'tours/FULL-DAY-LIMA-ANCESTRAL-5-1-1.jpg',
                'tours/FULL-DAY-LIMA-ANCESTRAL-4-1.jpg',
                'tours/FULL-DAY-LIMA-ANCESTRAL-6-1.jpg',
                'tours/FULL-DAY-LIMA-ANCESTRAL-7-1.jpg',
                'tours/CENTRO-HISTORICO-DE-LIMA-PARQUE-DE-LAS-AGUAS-4.jpg',
            ],
        ],
        'nazca-huacachina-2-dias' => [
            'cover_image' => 'tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5-1.jpg',
            'gallery' => [
                'tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5-1.jpg',
                'tours/2024-02-Nazca-03.webp',
                'tours/2024-02-Nazca-04.webp',
                'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-3.jpg',
                'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-8-scaled-1.jpg',
            ],
        ],
        'nazca-full-day' => [
            'cover_image' => 'tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5.jpg',
            'gallery' => [
                'tours/FULL-DAY-A-LAS-LINEAS-DE-NAZCA-5.jpg',
                'tours/2024-02-Nazca-05.webp',
                'tours/2024-02-Nazca-06.webp',
                'tours/2024-02-Nazca-09.webp',
            ],
        ],
        'city-tour-lima-catacumbas' => [
            'cover_image' => 'tours/2024-02-Centro-Historico-Lima-08.webp',
            'gallery' => [
                'tours/2024-02-Centro-Historico-Lima-08.webp',
                'tours/2024-02-Centro-Historico-Lima-09.webp',
                'tours/2024-02-Centro-Historico-Lima-11.webp',
                'tours/CENTRO-HISTORICO-DE-LIMA-PARQUE-DE-LAS-AGUAS-7.jpg',
            ],
        ],
        'machu-picchu-full-day' => [
            'cover_image' => 'tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg',
            'gallery' => [
                'tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg',
                'tours/MACHU-2-DIAS-1.jpg',
                'tours/2024-12-MACHU-2-DIAS-1-qulwpkgkbgqlczcpqjlzb39p7o3kkaw977pkcfyxdc.jpg',
                'tours/2024-12-MACHU-2-DIAS-2-qulwpgp7k4lg2ji6chzh147uu4m3pihbup3mfc4i28.jpg',
                'tours/pueblo-machu-picchu.jpg',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::FIXES as $slug => $fix) {
            $tour = Tour::withTrashed()->where('slug', $slug)->first();

            if (! $tour || ! $this->looksLikePlaceholder($tour->cover_image)) {
                continue;
            }

            $tour->update([
                'cover_image' => $fix['cover_image'],
                'gallery' => $fix['gallery'],
            ]);
        }
    }

    private function looksLikePlaceholder(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return str_contains($path, 'Rectangle')
            || str_ends_with($path, '/image.jpg')
            || str_ends_with($path, '/image-1.jpg');
    }
}
