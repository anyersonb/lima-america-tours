<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartReminder;
use App\Mail\AccountCredentials;
use App\Mail\BookingConfirmed;
use App\Mail\BookingPaymentReminder;
use App\Models\AbandonedCart;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que los 4 correos transaccionales (fix "sin fallback a otro
 * teléfono", ver ContactPhoneNoFallbackTest) se rendericen coherentes tanto
 * con teléfono cargado como sin él: sin el dato, la línea de contacto del
 * footer no debe imprimirse (nunca un "|" colgado ni un tel:/wa.me vacío),
 * y el correo debe seguir siendo legible y no exponer el número de otro
 * cliente (Lima View Tours) si el fallback llegara a reaparecer por error.
 */
class TransactionalEmailsPhoneCoherenceTest extends TestCase
{
    use RefreshDatabase;

    private function assertNoLegacyLimaViewPhone(string $html): void
    {
        $this->assertStringNotContainsString('925886725', $html);
    }

    private function makeBooking(Tour $tour): Booking
    {
        return Booking::create([
            'tour_id' => $tour->id,
            'tour_title_snapshot' => $tour->title_es,
            'customer_name' => 'Ana López',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '987000001',
            'travel_date' => now()->addDays(10)->format('Y-m-d'),
            'adults' => 2,
            'children' => 0,
            'unit_price' => 100.00,
            'total_price' => 200.00,
            'currency' => 'PEN',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'culqi',
            'payment_reference' => 'chr_test_email_phone',
            'locale' => 'es',
        ]);
    }

    public static function phoneStatesProvider(): array
    {
        return [
            'sin teléfono' => [''],
            'con teléfono' => ['+51 999 111 222'],
        ];
    }

    /** @dataProvider phoneStatesProvider */
    public function test_account_credentials_email_renders_coherently(string $phone): void
    {
        Setting::set('contact_phone', $phone);
        Setting::set('whatsapp', $phone ? '51999111222' : '');

        $customer = Customer::create([
            'name' => 'Ana López',
            'email' => 'ana@example.com',
            'password' => bcrypt('temporal123'),
            'locale' => 'es',
        ]);

        $html = (new AccountCredentials($customer, 'temporal123', 'es'))->render();

        $this->assertStringContainsString('Ana López', $html);
        $this->assertNoLegacyLimaViewPhone($html);
        if ($phone === '') {
            $this->assertStringNotContainsString('| </td>', $html, 'No debe quedar un separador "|" colgado sin teléfono detrás.');
        } else {
            $this->assertStringContainsString($phone, $html);
        }
    }

    /** @dataProvider phoneStatesProvider */
    public function test_booking_confirmed_email_renders_coherently(string $phone): void
    {
        Setting::set('contact_phone', $phone);
        Setting::set('whatsapp', $phone ? '51999111222' : '');

        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);
        $booking = $this->makeBooking($tour);

        $html = (new BookingConfirmed(collect([$booking]), $booking->customer_email))->render();

        $this->assertStringContainsString($booking->reference, $html);
        $this->assertNoLegacyLimaViewPhone($html);
        if ($phone === '') {
            $this->assertStringNotContainsString('Pagar por WhatsApp', $html);
        } else {
            $this->assertStringContainsString($phone, $html);
        }
    }

    /** @dataProvider phoneStatesProvider */
    public function test_booking_payment_reminder_email_renders_coherently(string $phone): void
    {
        Setting::set('contact_phone', $phone);
        Setting::set('whatsapp', $phone ? '51999111222' : '');

        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);
        $booking = $this->makeBooking($tour);
        $booking->forceFill(['payment_status' => 'pending'])->save();

        $html = (new BookingPaymentReminder(collect([$booking]), $booking->customer_email))->render();

        $this->assertStringContainsString($booking->reference, $html);
        $this->assertNoLegacyLimaViewPhone($html);
        if ($phone === '') {
            $this->assertStringNotContainsString('Pagar por WhatsApp', $html);
            $this->assertStringContainsString('Respóndenos a este correo', $html, 'Sin WhatsApp, el correo debe ofrecer una alternativa coherente (responder al correo).');
        } else {
            $this->assertStringContainsString('Pagar por WhatsApp', $html);
        }
    }

    /** @dataProvider phoneStatesProvider */
    public function test_abandoned_cart_email_renders_coherently(string $phone): void
    {
        Setting::set('contact_phone', $phone);
        Setting::set('whatsapp', $phone ? '51999111222' : '');

        $tour = Tour::factory()->create(['price' => 100, 'is_published' => true]);
        $cart = AbandonedCart::create([
            'email' => 'ana@example.com',
            'name' => 'Ana López',
            'locale' => 'es',
            'items' => [[
                'tour_id' => $tour->id,
                'title' => $tour->title_es,
                'quantity' => 2,
                'subtotal' => 200.0,
            ]],
            'subtotal' => 200.0,
            'total' => 200.0,
        ]);

        $html = (new AbandonedCartReminder($cart))->render();

        $this->assertStringContainsString('Ana López', $html);
        $this->assertNoLegacyLimaViewPhone($html);
        if ($phone !== '') {
            $this->assertStringContainsString($phone, $html);
        }
    }
}
