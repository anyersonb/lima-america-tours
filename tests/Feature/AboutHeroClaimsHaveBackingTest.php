<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La franja superior de Nosotros no publica lo que el negocio no sostiene.
 *
 * El mockup de esta pantalla trae cuatro trampas, y una es más fina que las
 * demás: **se queda con los números reales y les cambia la etiqueta**. Muestra
 * "13 Guías Locales Expertos" y "8 Años de Experiencia", que son exactamente el
 * 13 de *opiniones* y el 8 de *valores* de la barra real. Los guías activos son
 * 4 y el dato de años no existe (`company_started_year` está vacío). Un número
 * real mal etiquetado miente mejor que uno inventado, porque cuadra con todo lo
 * demás y nadie lo audita.
 *
 * Las otras tres: "Más de 2,500 viajeros satisfechos" y "Miles de viajeros"
 * (sin fuente en ninguna tabla), y "Atención 24/7" contra un horario publicado
 * de Lun-Vie 9:00-19:00.
 *
 * Ver docs/rebrand/LOTE-MOCKUPS-AGO-2026.md ("Lo que NO se publica, y por qué")
 * y docs/rebrand/inventario/00-VALIDACION-STAGING.md.
 */
class AboutHeroClaimsHaveBackingTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function about()
    {
        return $this->get(route('about', ['locale' => self::LOCALE]));
    }

    public function test_the_stats_bar_keeps_the_labels_that_match_its_numbers(): void
    {
        Testimonial::factory()->count(13)->create(['is_active' => true]);

        $response = $this->about()->assertOk();

        $response->assertSee('Opiniones de viajeros', false);
        $response->assertSee('Valores que nos guían', false);

        // Las etiquetas del mockup, que reetiquetan esos mismos números.
        $response->assertDontSee('Guías Locales Expertos', false);
        $response->assertDontSee('Años de Experiencia', false);
    }

    public function test_no_unbacked_traveller_count_is_published(): void
    {
        $response = $this->about()->assertOk();

        $response->assertDontSee('2,500', false);
        $response->assertDontSee('2.500', false);
        $response->assertDontSee('Miles de viajeros ya vivieron', false);
    }

    /**
     * El bloque de avatares está guardado por dato y hoy no se pinta, así que su
     * copy nunca se ve. Ese es justamente el riesgo: en cuanto el cliente suba
     * UNA foto, el bloque aparece con lo que diga ese texto. Este test lo fuerza
     * a aparecer para comprobar qué publicaría.
     */
    public function test_the_avatar_strip_shows_the_real_aggregate_once_it_has_a_photo(): void
    {
        Testimonial::factory()->count(4)->create([
            'is_active' => true,
            'rating' => 5,
            'avatar' => 'testimonials/una-foto-real.jpg',
        ]);

        $response = $this->about()->assertOk();

        $response->assertSee('opiniones de viajeros', false);
        $response->assertDontSee('Miles de viajeros ya vivieron', false);
    }

    public function test_without_photos_the_avatar_strip_is_not_rendered_at_all(): void
    {
        Testimonial::factory()->count(13)->create(['is_active' => true, 'avatar' => null]);

        $response = $this->about()->assertOk();

        $response->assertDontSee('lat-avatar-strip', false);
    }

    public function test_the_guarantee_strip_publishes_the_real_hours_instead_of_24_7(): void
    {
        Setting::set('contact_hours_es', 'Lun – Vie: 9:00 a.m. – 7:00 p.m.');

        $response = $this->about()->assertOk();

        $response->assertDontSee('24/7', false);
        $response->assertSee('Lun – Vie: 9:00 a.m. – 7:00 p.m.', false);
    }

    /**
     * Sin `company_started_year` el badge de antigüedad no se pinta. Es la única
     * mitad coherente de esta página: el H1 sigue afirmando "Más de 10 años" por
     * decisión escrita del jefe (about.blade.php), así que este test blinda al
     * menos que el badge no vuelva a aparecer por su cuenta.
     */
    public function test_the_years_badge_stays_hidden_without_a_start_year(): void
    {
        Setting::set('company_started_year', '');

        $response = $this->about()->assertOk();

        $response->assertDontSee('lat-split__badge', false);
    }
}
