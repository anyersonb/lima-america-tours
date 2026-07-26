<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use App\Services\BookingStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-level coverage for the single state-transition point shared by both
 * gateways and both webhook handlers (docs/pagos/PLAN-PASARELAS.md §4).
 * Uses RefreshDatabase (not a pure Unit test) because Booking::update()
 * needs a persisted row to operate on.
 */
class BookingStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function pendingBooking(array $overrides = []): Booking
    {
        $tour = Tour::factory()->create(['price' => 100.00]);

        return Booking::create(array_merge([
            'tour_id' => $tour->id,
            'tour_title_snapshot' => $tour->title_es,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '987654321',
            'travel_date' => now()->addDays(5)->format('Y-m-d'),
            'adults' => 1,
            'children' => 0,
            'unit_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'culqi',
            'payment_reference' => null,
            'locale' => 'es',
        ], $overrides));
    }

    public function test_pending_payment_can_transition_to_paid(): void
    {
        $booking = $this->pendingBooking();
        $machine = app(BookingStateMachine::class);

        $result = $machine->transition($booking, BookingStateMachine::PAID, ['payment_reference' => 'chr_x']);

        $this->assertTrue($result);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('paid', $booking->payment_status);
        $this->assertEquals('chr_x', $booking->payment_reference);
        $this->assertNull($booking->expires_at);
    }

    public function test_pending_payment_can_transition_to_failed(): void
    {
        $booking = $this->pendingBooking(['expires_at' => now()->addMinutes(20)]);
        $machine = app(BookingStateMachine::class);

        $result = $machine->transition($booking, BookingStateMachine::FAILED);

        $this->assertTrue($result);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals('failed', $booking->payment_status);
        // The hold is left alive so the customer can retry with another card.
        $this->assertNotNull($booking->expires_at);
    }

    public function test_failed_can_retry_into_paid(): void
    {
        $booking = $this->pendingBooking(['status' => 'pending', 'payment_status' => 'failed']);
        $machine = app(BookingStateMachine::class);

        $result = $machine->transition($booking, BookingStateMachine::PAID, ['payment_reference' => 'chr_retry']);

        $this->assertTrue($result);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('paid', $booking->payment_status);
    }

    public function test_transition_to_same_state_is_idempotent_noop(): void
    {
        $booking = $this->pendingBooking([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_reference' => 'chr_original',
        ]);
        $machine = app(BookingStateMachine::class);

        // Re-applying PAID with a DIFFERENT reference must not overwrite —
        // the no-op short-circuits before touching the row at all.
        $result = $machine->transition($booking, BookingStateMachine::PAID, ['payment_reference' => 'chr_other']);

        $this->assertFalse($result);
        $this->assertEquals('chr_original', $booking->fresh()->payment_reference);
    }

    public function test_paid_cannot_regress_to_failed(): void
    {
        $booking = $this->pendingBooking(['status' => 'confirmed', 'payment_status' => 'paid']);
        $machine = app(BookingStateMachine::class);

        $result = $machine->transition($booking, BookingStateMachine::FAILED);

        $this->assertFalse($result);
        $this->assertEquals('paid', $booking->fresh()->payment_status);
    }

    public function test_refunded_is_a_final_state(): void
    {
        $booking = $this->pendingBooking(['status' => 'refunded', 'payment_status' => 'refunded']);
        $machine = app(BookingStateMachine::class);

        $result = $machine->transition($booking, BookingStateMachine::PAID);

        $this->assertFalse($result);
        $this->assertEquals('refunded', $booking->fresh()->payment_status);
    }

    public function test_paid_can_transition_to_refunded_with_extra_columns(): void
    {
        $booking = $this->pendingBooking(['status' => 'confirmed', 'payment_status' => 'paid']);
        $machine = app(BookingStateMachine::class);

        $result = $machine->transition($booking, BookingStateMachine::REFUNDED, [
            'refunded_at' => now(),
            'refund_amount' => 100.00,
        ]);

        $this->assertTrue($result);
        $this->assertEquals('refunded', $booking->status);
        $this->assertEquals('refunded', $booking->payment_status);
        $this->assertNotNull($booking->refunded_at);
        $this->assertEquals('100.00', (string) $booking->refund_amount);
    }
}
