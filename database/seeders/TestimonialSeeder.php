<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * NO SIEMBRA NADA, A PROPÓSITO (2026-08-14).
 *
 * Este seeder creaba cinco reseñas de relleno —"Sara Fernández", "Rebeca
 * Figueroa", "Liam Carter", "Ana Suárez" y "Valeriy Roberts"— con
 * `is_featured = true`. Como la home y la ficha ordenan por destacadas, esas
 * cinco se publicaban ANTES que las reseñas reales: las tres tarjetas de
 * testimonios de la home eran las tres inventadas, y el agregado del hero
 * ("5.0 · 18 opiniones") las contaba junto a las de verdad. Dos de ellas
 * decían venir de Tripadvisor, así que la página de reseñas ofrecía un filtro
 * "Tripadvisor" que solo devolvía texto escrito por nosotros.
 *
 * Publicar una opinión de un cliente que no existe no es un pendiente de
 * configuración: es una reseña falsa a la vista del público, y en varios
 * países es además publicidad engañosa. El mismo criterio que ya aplicamos con
 * la foto de otro cliente en la galería y con las cifras sin respaldo del
 * hero: un default que publica algo que no es del cliente es un defecto.
 *
 * Las reseñas REALES entran por otras dos vías, y ninguna necesita este
 * seeder:
 *   - `php artisan reviews:import-wp` — las 13 del WordPress de
 *     limaamericatours.com (guía Augusto), idempotentes por `external_ref`.
 *   - HuacachinaReviewsSeeder — las 6 migradas del WooCommerce de producción
 *     para el tour de Huacachina, con nombre y fecha reales.
 *   - Y el formulario público de /resenas, que las deja pendientes de revisión.
 *
 * La clase se conserva (en vez de borrarla) porque DatabaseSeeder la invoca y
 * porque el nombre libre invitaría a alguien a volver a crearla con datos de
 * relleno. Lo vigila TestimonialsAreNotSeededTest.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        // Intencionalmente vacío. Ver el bloque de arriba antes de agregar nada.
    }
}
