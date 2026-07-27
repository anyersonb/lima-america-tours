<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA de reserva: Booking::booted() generaba la referencia con el prefijo
 * `LVT-` (Lima View Tours), heredado del proyecto original. Este proyecto es
 * Lima América Tours, así que las reservas nuevas deben referenciarse con
 * `LAT-`. Las reservas ya existentes conservan su referencia LVT- (no hay
 * backfill: solo cambia la generación para registros nuevos).
 */
class BookingReferencePrefixTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_booking_reference_starts_with_lat_prefix(): void
    {
        $tour = Tour::factory()->create(['price' => 100.00, 'is_published' => true]);

        $booking = Booking::create([
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
            'payment_reference' => 'chr_test_reference_prefix',
            'locale' => 'es',
        ]);

        $this->assertNotEmpty($booking->reference);
        $this->assertStringStartsWith('LAT-', $booking->reference);
        $this->assertStringNotContainsString('LVT-', $booking->reference);
    }
}
