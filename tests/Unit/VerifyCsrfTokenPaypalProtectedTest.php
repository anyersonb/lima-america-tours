<?php

namespace Tests\Unit;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Las rutas de PayPal EXIGEN CSRF (2026-07-29).
 *
 * Historia, porque este test afirmaba lo contrario: mientras PayPal estuvo en
 * pausa, las dos rutas eran closures `abort(404)` y se metieron en `$except`
 * solo para que un POST diera 404 en vez de 419 (CRO #6). Al reactivarse
 * (crean y capturan órdenes de pago de verdad) esa exención pasó a ser un
 * agujero: cualquier sitio podría dispararlas desde el navegador de un
 * visitante logueado. Vuelven a estar protegidas.
 *
 * Un Feature test normal (postJson a la ruta) NO puede comprobar esto: Laravel
 * desactiva CSRF cuando `runningUnitTests()` es true
 * (vendor/laravel/framework/.../VerifyCsrfToken::handle()), así que el POST
 * pasaría con o sin exención (falso verde). Por eso se llama directamente al
 * mecanismo que decide si el middleware corta la request: `inExceptArray()`.
 */
class VerifyCsrfTokenPaypalProtectedTest extends TestCase
{
    private function isExcepted(string $path): bool
    {
        $request = Request::create('http://localhost'.$path, 'POST');

        $middleware = app(VerifyCsrfToken::class);

        $method = new \ReflectionMethod(VerifyCsrfToken::class, 'inExceptArray');
        $method->setAccessible(true);

        return (bool) $method->invoke($middleware, $request);
    }

    public function test_paypal_create_route_requires_csrf_for_all_locales(): void
    {
        $this->assertFalse($this->isExcepted('/es/checkout/paypal/create'));
        $this->assertFalse($this->isExcepted('/en/checkout/paypal/create'));
        $this->assertFalse($this->isExcepted('/pt/checkout/paypal/create'));
    }

    public function test_paypal_capture_route_requires_csrf_for_all_locales(): void
    {
        $this->assertFalse($this->isExcepted('/es/checkout/paypal/capture'));
        $this->assertFalse($this->isExcepted('/en/checkout/paypal/capture'));
        $this->assertFalse($this->isExcepted('/pt/checkout/paypal/capture'));
    }

    public function test_unrelated_checkout_routes_are_not_excepted(): void
    {
        // Guard against an overly broad glob accidentally exempting the real
        // payment endpoint (checkout.process) from CSRF protection.
        $this->assertFalse($this->isExcepted('/es/checkout/procesar'));
    }
}
