<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;

/**
 * Los 4 nombres reales confirmados por el cliente el 2026-08-10 para la
 * sección "Guías locales, amigos y amantes de nuestra cultura" (mockup
 * 02-nosotros-parte1). Idempotente a propósito (updateOrCreate por `name`,
 * no una migration): re-ejecutar este seeder nunca duplica filas ni pisa
 * cambios que el cliente haga luego desde Filament en otros campos que este
 * seeder no toca (se puede volver a correr sin miedo tras un `migrate:fresh`).
 *
 * Sin foto: docs/rebrand/CONTENIDO-REAL-PRODUCCION.md §3.1 tiene 5 retratos
 * reales del equipo (uniformados, en locación) en el volcado de WordPress,
 * pero el propio documento marca la correspondencia nombre↔foto como NO
 * confirmada ("con alta probabilidad Augusto", nunca un "sí" del cliente).
 * Poner una de esas fotos a una de estas 4 personas sería adivinar una cara
 * — exactamente lo que la regla "no fotos de otra persona" prohíbe. El
 * campo queda vacío hasta que el cliente confirme cuál foto es cuál.
 *
 * Sin Instagram/WhatsApp: ningún handle real fue provisto. El mockup solo
 * pinta esos íconos cuando el campo tiene valor (ver Guide::getInstagramUrlAttribute
 * / getWhatsappUrlAttribute), así que dejarlos vacíos los oculta solos.
 */
class GuideSeeder extends Seeder
{
    public function run(): void
    {
        $guides = [
            [
                'name' => 'Samira',
                'role_es' => 'Guía Oficial de Turismo',
                'role_en' => 'Official Tourism Guide',
                'role_pt' => 'Guia Oficial de Turismo',
                'bio_es' => 'Apasionada por la historia de Lima y la cultura peruana.',
                'bio_en' => 'Passionate about the history of Lima and Peruvian culture.',
                'bio_pt' => 'Apaixonada pela história de Lima e pela cultura peruana.',
                // Respaldo verificable: docs/rebrand/CONTENIDO-REAL-PRODUCCION.md §3.2,
                // conteo manual de reseñas de Google + TripAdvisor que la mencionan por
                // nombre (2025-12).
                'highlight_es' => 'Mencionada por nombre en 15 de 20 reseñas de Google y TripAdvisor.',
                'highlight_en' => 'Mentioned by name in 15 of 20 Google and TripAdvisor reviews.',
                'highlight_pt' => 'Mencionada pelo nome em 15 de 20 avaliações do Google e TripAdvisor.',
                'order' => 1,
            ],
            [
                'name' => 'Nikki',
                'role_es' => 'Guía Oficial de Turismo',
                'role_en' => 'Official Tourism Guide',
                'role_pt' => 'Guia Oficial de Turismo',
                'bio_es' => 'Experta en historia, arte y experiencias locales.',
                'bio_en' => 'Expert in history, art and local experiences.',
                'bio_pt' => 'Especialista em história, arte e experiências locais.',
                'order' => 2,
            ],
            [
                'name' => 'Arturo',
                'role_es' => 'Guía Oficial de Turismo',
                'role_en' => 'Official Tourism Guide',
                'role_pt' => 'Guia Oficial de Turismo',
                'bio_es' => 'Amante de la aventura y las buenas historias.',
                'bio_en' => 'Lover of adventure and good stories.',
                'bio_pt' => 'Amante de aventura e boas histórias.',
                'order' => 3,
            ],
            [
                'name' => 'Augusto',
                'role_es' => 'Fundador',
                'role_en' => 'Founder',
                'role_pt' => 'Fundador',
                'bio_es' => 'Emprendedor y viajero, comprometido con brindar experiencias auténticas.',
                'bio_en' => 'Entrepreneur and traveler, committed to delivering authentic experiences.',
                'bio_pt' => 'Empreendedor e viajante, comprometido em oferecer experiências autênticas.',
                'order' => 4,
            ],
        ];

        foreach ($guides as $data) {
            Guide::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
