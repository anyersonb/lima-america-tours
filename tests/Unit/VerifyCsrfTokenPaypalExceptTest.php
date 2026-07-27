<?php

namespace Tests\Unit;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * CRO #6: POST a /checkout/paypal/create|capture debía dar 404 igual que GET
 * (la ruta está deshabilitada con un closure `abort(404)` en routes/web.php),
 * pero VerifyCsrfToken interceptaba el POST *antes* de llegar al closure y
 * respondía 419, porque esas dos rutas no estaban en el array `$except`.
 *
 * Un Feature test normal (postJson a la ruta) NO puede reproducir el 419:
 * Laravel desactiva CSRF automáticamente cuando `runningUnitTests()` es true
 * (vendor/laravel/framework/.../VerifyCsrfToken::handle()), así que el POST
 * llegaría al closure con o sin el fix y el test pasaría en ambos casos
 * (falso verde). Por eso este test llama directamente al mecanismo real que
 * decide si el middleware corta la request: `inExceptArray()`.
 */
class VerifyCsrfTokenPaypalExceptTest extends TestCase
{
    private function isExcepted(string $path): bool
    {
        $request = Request::create('http://localhost'.$path, 'POST');

        $middleware = app(VerifyCsrfToken::class);

        $method = new \ReflectionMethod(VerifyCsrfToken::class, 'inExceptArray');
        $method->setAccessible(true);

        return (bool) $method->invoke($middleware, $request);
    }

    public function test_paypal_create_route_is_excepted_from_csrf_for_all_locales(): void
    {
        $this->assertTrue($this->isExcepted('/es/checkout/paypal/create'));
        $this->assertTrue($this->isExcepted('/en/checkout/paypal/create'));
        $this->assertTrue($this->isExcepted('/pt/checkout/paypal/create'));
    }

    public function test_paypal_capture_route_is_excepted_from_csrf_for_all_locales(): void
    {
        $this->assertTrue($this->isExcepted('/es/checkout/paypal/capture'));
        $this->assertTrue($this->isExcepted('/en/checkout/paypal/capture'));
        $this->assertTrue($this->isExcepted('/pt/checkout/paypal/capture'));
    }

    public function test_unrelated_checkout_routes_are_not_excepted(): void
    {
        // Guard against an overly broad glob accidentally exempting the real
        // payment endpoint (checkout.process) from CSRF protection.
        $this->assertFalse($this->isExcepted('/es/checkout/procesar'));
    }
}
