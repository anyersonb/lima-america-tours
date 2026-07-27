<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Tour;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'es';

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    private function tour(array $overrides = []): Tour
    {
        return Tour::factory()->create(array_merge([
            'price' => 150.00,
            'is_published' => true,
        ], $overrides));
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function addTourToCart(Tour $tour): void
    {
        $this->cartService()->add($tour, 2, 1, now()->addDays(10)->format('Y-m-d'));
    }

    private function validPaymentPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Juan Pérez García',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(15)->format('Y-m-d'),
            'culqi_token' => 'tkn_test_abc123',
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────
    //  Tests
    // ─────────────────────────────────────────────────────────────

    public function test_payment_form_redirects_when_cart_empty(): void
    {
        $response = $this->get(route('checkout.pay', ['locale' => self::LOCALE]));

        $response->assertRedirectToRoute('cart.index', ['locale' => self::LOCALE]);
        $response->assertSessionHas('error');
    }

    public function test_payment_form_renders_with_items(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->get(route('checkout.pay', ['locale' => self::LOCALE]));

        $response->assertOk();
        $response->assertViewIs('checkout.payment');
        $response->assertViewHas('items');
        $response->assertViewHas('total');
        $response->assertViewHas('public_key');
        $response->assertViewHas('total_centavos');
    }

    public function test_process_payment_with_valid_token_creates_booking_and_charge(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        // Mock Culqi HTTP response
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_test_abc123',
                'amount' => 45000,
                'currency_code' => 'PEN',
                'object' => 'charge',
                'outcome' => ['type' => 'venta_exitosa'],
            ], 201),
        ]);

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->validPaymentPayload()
        );

        $response->assertRedirectToRoute('checkout.thanks', ['locale' => self::LOCALE]);

        // Booking was created and marked paid, in soles (PEN) — never USD,
        // to avoid a ~3.7x overcharge (the tour price is already in soles).
        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'payment_reference' => 'chr_test_abc123',
            'payment_method' => 'culqi',
            'currency' => 'PEN',
        ]);
    }

    public function test_process_payment_with_failed_token_marks_booking_failed(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        // Mock Culqi returning a 422 / error
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'object' => 'error',
                'type' => 'card_error',
                'user_message' => 'La tarjeta fue rechazada.',
            ], 422),
        ]);

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->validPaymentPayload()
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // At least one booking was created and marked failed
        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'juan@example.com',
            'payment_status' => 'failed',
        ]);
    }

    public function test_validation_rejects_invalid_email_phone_date(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            [
                'customer_name' => 'Test User',
                'customer_email' => 'not-an-email',
                'customer_phone' => '12345',          // invalid format
                'travel_date' => now()->subDay()->format('Y-m-d'), // past date
                'culqi_token' => 'tkn_test',
            ]
        );

        $response->assertSessionHasErrors(['customer_email', 'customer_phone', 'travel_date']);
    }

    /**
     * Defecto #7/§e de docs/qa/F7-personas.md §g (F7, cro-validator):
     * "el campo customer name es obligatorio" (así, en inglés a medias).
     * ProcessPaymentRequest::attributes() ahora mapea cada campo a su
     * etiqueta en español, así el mensaje genérico de Laravel usa "nombre",
     * "correo electrónico" y "teléfono" en vez del nombre técnico del campo.
     */
    public function test_validation_errors_use_spanish_field_names_not_technical_english(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            [
                'payment_timing' => 'later',
                'travel_date' => now()->addDays(15)->format('Y-m-d'),
                // customer_name, customer_email, customer_phone omitidos a propósito
            ]
        );

        $response->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone']);

        $errors = session('errors');

        $this->assertSame('El campo nombre es obligatorio.', $errors->first('customer_name'));
        $this->assertSame('El campo correo electrónico es obligatorio.', $errors->first('customer_email'));
        $this->assertSame('El campo teléfono es obligatorio.', $errors->first('customer_phone'));

        $this->assertStringNotContainsString('customer name', $errors->first('customer_name'));
        $this->assertStringNotContainsString('customer email', $errors->first('customer_email'));
        $this->assertStringNotContainsString('customer phone', $errors->first('customer_phone'));
    }

    public function test_thanks_page_renders_after_successful_payment(): void
    {
        $tour = $this->tour();

        // Simulate session with last_bookings
        $booking = Booking::create([
            'tour_id' => $tour->id,
            'tour_title_snapshot' => $tour->title_es,
            'customer_name' => 'Ana López',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '987000001',
            'travel_date' => now()->addDays(10)->format('Y-m-d'),
            'adults' => 2,
            'children' => 0,
            'unit_price' => 150.00,
            'total_price' => 300.00,
            'currency' => 'PEN',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'culqi',
            'payment_reference' => 'chr_test_xxx',
            'locale' => 'es',
        ]);

        $response = $this->withSession(['last_bookings' => [$booking->toArray()]])
            ->get(route('checkout.thanks', ['locale' => self::LOCALE]));

        $response->assertOk();
        $response->assertViewIs('checkout.thanks');
        $response->assertViewHas('bookings');
    }

    public function test_booking_email_is_queued_after_success(): void
    {
        Mail::fake();

        $tour = $this->tour();
        $this->addTourToCart($tour);

        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_test_mail_check',
                'amount' => 45000,
                'currency_code' => 'PEN',
                'object' => 'charge',
            ], 201),
        ]);

        $this->post(
            route('checkout.process', ['locale' => self::LOCALE]),
            $this->validPaymentPayload()
        );

        // BookingConfirmed implements ShouldQueue; with QUEUE_CONNECTION=sync
        // and Mail::fake() the mailable is captured as "queued"
        Mail::assertQueued(BookingConfirmed::class, function (BookingConfirmed $mail): bool {
            return $mail->toEmail === 'juan@example.com';
        });
    }

    /**
     * Bug crítico: el checkout completo estaba codificado en USD mientras los
     * 26 tours reales (importados de WordPress) tienen currency=PEN y price
     * en soles — esto cobraría el número de soles como si fueran dólares
     * (~3.7x de sobrecobro real). Este test verifica que la página del
     * carrito/checkout (checkout.blade.php, vía cart.index) muestra el
     * símbolo de soles y no deja ningún "USD" visible sobre un monto.
     */
    public function test_checkout_page_shows_soles_and_not_usd(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->get(route('cart.index', ['locale' => self::LOCALE]));

        $response->assertOk();
        $response->assertSee('S/', false);
        $response->assertDontSee('USD');
    }

    /**
     * CRO #5: el nodo del total dinámico (recalculado en JS al cambiar
     * adultos/niños) llevaba `data-total="usd2"` + una función `fmtUsd2()`
     * que formateaba con 2 decimales — residuo del nombre/formato de cuando
     * el sitio cotizaba en dólares. No se mostraba al usuario como texto,
     * pero es exactamente el tipo de residuo que un futuro mantenimiento
     * puede reactivar por error. El total dinámico ahora es siempre soles
     * vía la misma fmt() que el resto del carrito.
     */
    public function test_checkout_page_html_has_no_usd2_residue(): void
    {
        $tour = $this->tour();
        $this->addTourToCart($tour);

        $response = $this->get(route('cart.index', ['locale' => self::LOCALE]));

        $response->assertOk();
        $response->assertDontSee('usd2', false);
        $response->assertDontSee('fmtUsd2', false);
    }

    /**
     * Verifica que el payload enviado a Culqi (api.culqi.com/v2/charges) se
     * construya con currency=PEN. Se prueba PaymentService::createCharge()
     * directamente —el servicio ya existente que centraliza la llamada HTTP—
     * en vez de a través de CheckoutController::processPayment(), porque hoy
     * ese controller no invoca el cobro directo con Culqi para "pagar ahora"
     * (ver nota de "4 fallos baseline" en el reporte): el flujo "now" fue
     * migrado a PayPal y el cableado de Culqi sigue en curso (tarea aparte).
     * Este test documenta y protege la moneda correcta del payload para
     * cuando ese cableado se complete.
     */
    public function test_culqi_charge_payload_uses_pen_currency(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_test_pen_payload',
                'amount' => 45000,
                'currency_code' => 'PEN',
                'object' => 'charge',
                'outcome' => ['type' => 'venta_exitosa'],
            ], 201),
        ]);

        app(\App\Services\PaymentService::class)->createCharge([
            'amount' => 45000,
            'currency' => 'PEN',
            'email' => 'juan@example.com',
            'source_id' => 'tkn_test_abc123',
        ]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'https://api.culqi.com/v2/charges'
                && $request['currency'] === 'PEN';
        });
    }
}
