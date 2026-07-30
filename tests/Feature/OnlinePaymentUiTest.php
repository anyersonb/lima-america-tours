<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tour;
use App\Services\CartService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La página de pago (`/checkout/pago`) ofrece SOLO las pasarelas que de verdad
 * pueden cobrar.
 *
 * El riesgo que cubre: un método de pago pintado sin credenciales detrás lleva
 * al cliente a llenar todos sus datos y fallar en el último clic — peor que no
 * ofrecerlo. Antes la vista tenía "card" siempre habilitado y PayPal fijo en
 * "próximamente", los dos escritos a mano, así que la pantalla no tenía
 * relación con lo que el servidor podía procesar.
 *
 * También fija dos cosas que costaron caro en producción de otros proyectos:
 *  - la llave pública de Culqi sale del panel, no del .env (con las claves
 *    cargadas en Configuración → Pagos, la vista se quedaba con la del .env y
 *    la tokenización fallaba sin decir por qué);
 *  - el SDK de PayPal se carga con la MISMA moneda de la orden que crea el
 *    servidor, o PayPal la rechaza al aprobarla.
 */
class OnlinePaymentUiTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    private function fillCart(): void
    {
        $tour = Tour::factory()->create(['price' => 300.00, 'is_published' => true]);

        app(CartService::class)->add($tour, 2, 0, now()->addDays(10)->format('Y-m-d'));
    }

    private function enableCulqi(): void
    {
        Setting::set('culqi_public_key', 'pk_test_llave_de_prueba');
        Setting::set('culqi_secret_key', 'sk_test_llave_de_prueba');
    }

    private function enablePaypal(): void
    {
        Setting::set('paypal_client_id', 'sandbox-client-id-abc');
        Setting::set('paypal_secret', 'sandbox-secret-xyz');
        Setting::set('paypal_mode', 'sandbox');
    }

    public function test_without_credentials_no_gateway_is_offered(): void
    {
        // El .env de desarrollo trae los placeholders pk_test_REPLACE_ME.
        config(['services.culqi.public_key' => 'pk_test_REPLACE_ME']);

        $this->fillCart();

        $response = $this->get(route('checkout.pay', ['locale' => self::LOCALE]))->assertOk();

        $response->assertDontSee('checkout.culqi.com', false);
        $response->assertDontSee('paypal.com/sdk/js', false);
        $response->assertDontSee('id="paypal-buttons"', false);

        // Sin pasarela, "pagar ahora" no se ofrece: queda el flujo que sí
        // funciona (reservar y confirmar por WhatsApp/correo).
        $response->assertDontSee('value="now"', false);
        $response->assertSee('value="later"', false);
    }

    /**
     * OJO al escribir estos tests: NO sirve pedir la página, cambiar los
     * Settings y volver a pedirla en el MISMO test. Laravel cachea la
     * instancia del controller dentro del objeto Route, que vive todo el
     * proceso de pruebas, así que el segundo request reusa el controller —
     * y con él los servicios inyectados con las credenciales viejas. Da un
     * falso rojo que parece un bug de caché de la app y no lo es. Cada
     * escenario va en su propio test, con su app limpia.
     */
    public function test_paypal_is_not_offered_with_only_the_client_id(): void
    {
        $this->fillCart();

        // Con solo el Client ID el botón se pintaría y reventaría al crear la
        // orden: el cliente llega al último clic para fallar ahí.
        Setting::set('paypal_client_id', 'sandbox-client-id-abc');

        $this->get(route('checkout.pay', ['locale' => self::LOCALE]))
            ->assertOk()
            ->assertDontSee('id="paypal-buttons"', false)
            ->assertDontSee('paypal.com/sdk/js', false);
    }

    public function test_paypal_button_appears_with_both_credentials(): void
    {
        $this->fillCart();
        $this->enablePaypal();

        $response = $this->get(route('checkout.pay', ['locale' => self::LOCALE]))->assertOk();

        $response->assertSee('id="paypal-buttons"', false);
        $response->assertSee('client-id=sandbox-client-id-abc', false);
        // La moneda del SDK es la del sitio, no un literal.
        $response->assertSee('currency='.Money::site(), false);
    }

    public function test_culqi_public_key_comes_from_the_panel_not_the_env(): void
    {
        config(['services.culqi.public_key' => 'pk_test_del_env']);

        $this->fillCart();
        $this->enableCulqi();

        $response = $this->get(route('checkout.pay', ['locale' => self::LOCALE]))->assertOk();

        $response->assertSee('checkout.culqi.com', false);
        $response->assertSee('pk_test_llave_de_prueba', false);
        $response->assertDontSee('pk_test_del_env', false);
    }

    public function test_paypal_is_not_offered_in_a_currency_paypal_cannot_charge(): void
    {
        // PayPal no admite PEN: aunque las credenciales estén cargadas, en un
        // sitio en soles no se puede ofrecer.
        Setting::set('site_currency', 'PEN');
        $this->enablePaypal();

        $tour = Tour::factory()->create(['price' => 300.00, 'currency' => 'PEN', 'is_published' => true]);
        app(CartService::class)->add($tour, 2, 0, now()->addDays(10)->format('Y-m-d'));

        $this->get(route('checkout.pay', ['locale' => self::LOCALE]))
            ->assertOk()
            ->assertDontSee('id="paypal-buttons"', false)
            ->assertDontSee('paypal.com/sdk/js', false);
    }

    public function test_pay_now_is_offered_once_a_gateway_is_configured(): void
    {
        $this->fillCart();
        $this->enableCulqi();

        $this->get(route('checkout.pay', ['locale' => self::LOCALE]))
            ->assertOk()
            ->assertSee('value="now"', false);
    }
}
