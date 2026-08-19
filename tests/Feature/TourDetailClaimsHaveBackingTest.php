<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La ficha de tour no publica afirmaciones que el negocio no sostiene.
 *
 * Los tres casos de acá se midieron vivos en staging el 2026-08-18 sobre el
 * commit 708ed8a (docs/rebrand/inventario/00-VALIDACION-STAGING.md), no se
 * dedujeron leyendo el código:
 *
 *   · "Atención al cliente 24/7" mientras el horario publicado por el propio
 *     cliente es Lun-Vie 9:00-19:00.
 *   · "Pago 100% seguro" con las llaves de Culqi y PayPal en modo prueba, o
 *     sea sin ninguna pasarela capaz de cobrar.
 *   · "4.8" con "(0 reseñas)" al lado, en la ficha y en las tres tarjetas de
 *     relacionados. La columna `rating` viene sembrada con el mismo 4.8 para
 *     los 24 tours publicados.
 *
 * Todas están en la tabla "Lo que NO se publica, y por qué" de
 * docs/rebrand/LOTE-MOCKUPS-AGO-2026.md.
 */
class TourDetailClaimsHaveBackingTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function publishedTour(array $attributes = []): Tour
    {
        return Tour::factory()->create($attributes + [
            'is_published' => true,
            'price' => 69.00,
        ]);
    }

    private function showTour(Tour $tour)
    {
        return $this->get(route('tours.show', [
            'locale' => self::LOCALE,
            'slug' => $tour->slug,
        ]));
    }

    public function test_the_guarantee_strip_publishes_the_real_hours_instead_of_24_7(): void
    {
        Setting::set('contact_hours_es', 'Lun – Vie: 9:00 a.m. – 7:00 p.m.');

        $response = $this->showTour($this->publishedTour())->assertOk();

        $response->assertDontSee('24/7', false);
        $response->assertSee('Lun – Vie: 9:00 a.m. – 7:00 p.m.', false);
    }

    public function test_without_a_gateway_the_booking_box_does_not_promise_secure_payment(): void
    {
        // Las llaves de prueba son justo lo que hay cargado hoy en staging.
        config(['services.culqi.public_key' => 'pk_test_REPLACE_ME']);

        $response = $this->showTour($this->publishedTour())->assertOk();

        $response->assertDontSee('Pago 100% seguro', false);
        $response->assertSee('Sin cobro ahora', false);
    }

    public function test_with_a_gateway_the_booking_box_does_promise_secure_payment(): void
    {
        Setting::set('culqi_public_key', 'pk_test_llave_de_prueba');
        Setting::set('culqi_secret_key', 'sk_test_llave_de_prueba');

        $response = $this->showTour($this->publishedTour())->assertOk();

        $response->assertSee('Pago 100% seguro', false);
        $response->assertDontSee('Sin cobro ahora', false);
    }

    public function test_a_rating_with_zero_reviews_is_not_published(): void
    {
        $tour = $this->publishedTour(['rating' => 4.8, 'reviews_count' => 0]);

        $response = $this->showTour($tour)->assertOk();

        $response->assertDontSee('lat-stars__rate', false);
        $response->assertDontSee('0 reseñas', false);
    }

    public function test_a_rating_backed_by_real_reviews_is_published(): void
    {
        $tour = $this->publishedTour(['rating' => 4.9, 'reviews_count' => 238]);

        $response = $this->showTour($tour)->assertOk();

        $response->assertSee('lat-stars__rate', false);
        $response->assertSee('4.9', false);
        $response->assertSee('238', false);
    }

    /**
     * El defecto no vivía solo en la ficha: las tarjetas de "También te puede
     * interesar" repetían el mismo "4.8 (0)" tres veces más por página.
     */
    public function test_related_tour_cards_also_hide_an_unbacked_rating(): void
    {
        $tour = $this->publishedTour(['rating' => 4.8, 'reviews_count' => 0]);
        $this->publishedTour([
            'region_id' => $tour->region_id,
            'rating' => 4.8,
            'reviews_count' => 0,
        ]);

        $response = $this->showTour($tour)->assertOk();

        $response->assertSee('lat-mini-tour', false);
        $response->assertDontSee('lat-stars__rate', false);
    }
}
