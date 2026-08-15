<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El sitio no puede publicar reseñas que no escribió ningún cliente.
 *
 * Hallazgo del 2026-08-14 (lo preguntó el jefe mirando /resenas): las cinco
 * primeras reseñas del listado —Sara Fernández, Rebeca Figueroa, Liam Carter,
 * Ana Suárez y Valeriy Roberts— no venían de ninguna parte: las creaba
 * `TestimonialSeeder` con `is_featured = true`. Como la home ordena por
 * destacadas, las TRES tarjetas de testimonios de la portada eran las tres
 * inventadas, mientras las 13 reales importadas del WordPress quedaban abajo.
 * El agregado del hero decía "18 opiniones" contando ambas cosas, y dos de las
 * falsas se atribuían a Tripadvisor, de donde no había ninguna.
 *
 * Una reseña falsa no es un dato de relleno como un lorem ipsum: es un
 * testimonio atribuido a una persona que no existe, a la vista del público.
 *
 * Este test es el que impide que vuelvan. Se verificó que FALLA con el seeder
 * anterior (5 filas sembradas) antes de darlo por bueno.
 */
class TestimonialsAreNotSeededTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_de_testimonios_no_crea_ninguna_resena(): void
    {
        $this->seed(\Database\Seeders\TestimonialSeeder::class);

        $sembradas = Testimonial::pluck('name')->all();

        $this->assertSame(
            [],
            $sembradas,
            'TestimonialSeeder volvió a sembrar reseñas: '.implode(', ', $sembradas)
        );
    }

    public function test_ninguna_resena_publicada_carece_de_origen_comprobable(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Toda reseña activa tiene que poder rastrearse a algo real: el id del
        // WordPress del que se importó (external_ref), o el tour al que se
        // migró desde WooCommerce (tour_id, con su fecha). Una fila activa sin
        // ninguna de las dos cosas es texto que escribimos nosotros.
        $huerfanas = Testimonial::where('is_active', true)
            ->whereNull('external_ref')
            ->whereNull('tour_id')
            ->pluck('name')
            ->all();

        $this->assertSame(
            [],
            $huerfanas,
            'Hay reseñas publicadas sin origen comprobable (ni external_ref ni tour): '.implode(', ', $huerfanas)
        );
    }
}
