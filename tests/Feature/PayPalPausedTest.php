<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PayPal is EN PAUSA server-side (not cancelled): the client's decision is
 * "pagar ahora" = Culqi in PEN, with PayPal reactivation deferred until the
 * currency question is resolved (docs/pagos/PLAN-PASARELAS.md §13.2).
 *
 * routes/web.php disables checkout.paypal.create / checkout.paypal.capture
 * (they now 404 before reaching CheckoutController::paypalCreateOrder /
 * paypalCaptureOrder) so a live endpoint can't create/capture an order in
 * USD hardcoded while the business charges in soles. The controller methods
 * themselves are intentionally left untouched, ready to be re-wired once
 * PayPal is reactivated.
 *
 * Before this change both routes reached the controller and responded
 * 200/JSON (create) or 4xx/5xx JSON from validation/business logic
 * (capture) — never a routing 404. This test locks in the paused state.
 *
 * CRO #6: a GET already returned 404, but a POST was intercepted by
 * VerifyCsrfToken (no valid _token in a plain POST) *before* reaching the
 * closure, responding 419 instead of 404. Both routes are now excluded from
 * CSRF verification (app/Http/Middleware/VerifyCsrfToken.php) so POST also
 * hits the `abort(404)` closure. This test locks in 404 for GET *and* POST.
 */
class PayPalPausedTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    public function test_paypal_create_order_route_is_disabled_for_get(): void
    {
        Http::fake(); // any call reaching PayPal here would be a bug

        $response = $this->get(route('checkout.paypal.create', ['locale' => self::LOCALE]));

        $response->assertStatus(404);
        Http::assertNothingSent();
    }

    public function test_paypal_create_order_route_is_disabled_for_post(): void
    {
        Http::fake(); // any call reaching PayPal here would be a bug

        $response = $this->postJson(route('checkout.paypal.create', ['locale' => self::LOCALE]));

        $response->assertStatus(404);
        Http::assertNothingSent();
    }

    public function test_paypal_capture_order_route_is_disabled_for_get(): void
    {
        Http::fake(); // any call reaching PayPal here would be a bug

        $response = $this->get(route('checkout.paypal.capture', ['locale' => self::LOCALE]));

        $response->assertStatus(404);
        Http::assertNothingSent();
    }

    public function test_paypal_capture_order_route_is_disabled_for_post(): void
    {
        Http::fake(); // any call reaching PayPal here would be a bug

        $response = $this->postJson(route('checkout.paypal.capture', ['locale' => self::LOCALE]), [
            'orderID' => 'ORDER-WHATEVER',
        ]);

        $response->assertStatus(404);
        Http::assertNothingSent();
    }
}
