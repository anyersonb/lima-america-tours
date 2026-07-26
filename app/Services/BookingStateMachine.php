<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Single source of truth for booking payment-state transitions.
 *
 * Reused by both gateways (Culqi charge flow, PayPal capture flow) and by
 * both webhook handlers (WebhookController::culqi / ::paypal), so a booking
 * can never be pushed into an inconsistent (status, payment_status) pair and
 * webhooks/controllers agree on what "already processed" means.
 *
 * See docs/pagos/PLAN-PASARELAS.md §4 for the full state diagram this class
 * implements.
 */
class BookingStateMachine
{
    // Logical states — these are what callers reason about; they map to the
    // existing `status` + `payment_status` string columns under the hood.
    public const PENDING_PAYMENT = 'pending_payment';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const PARTIALLY_PAID = 'partially_paid';

    public const REFUNDED = 'refunded';

    public const ABANDONED = 'abandoned';

    /**
     * Minutes a "pending_payment" hold stays valid for before it can be
     * considered abandoned (docs/pagos/PLAN-PASARELAS.md §4.3). No cron job
     * consumes this yet in this phase — the column is populated so a future
     * expiration command has something to act on.
     */
    public const HOLD_MINUTES = 20;

    /** @var array<string, array<string, mixed>> */
    private const STATE_COLUMNS = [
        self::PENDING_PAYMENT => ['status' => 'pending', 'payment_status' => 'pending'],
        self::PAID => ['status' => 'confirmed', 'payment_status' => 'paid', 'expires_at' => null],
        self::FAILED => ['status' => 'pending', 'payment_status' => 'failed'],
        self::PARTIALLY_PAID => ['status' => 'confirmed', 'payment_status' => 'partially_paid', 'expires_at' => null],
        self::REFUNDED => ['status' => 'refunded', 'payment_status' => 'refunded', 'expires_at' => null],
        self::ABANDONED => ['status' => 'cancelled', 'payment_status' => 'expired'],
    ];

    /**
     * Allowed target states reachable from each logical state.
     * A state reaching itself is NOT listed here — that case is handled as
     * an idempotent no-op by transition() before this map is even consulted.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        self::PENDING_PAYMENT => [self::PAID, self::FAILED, self::PARTIALLY_PAID, self::ABANDONED],
        self::FAILED => [self::PAID, self::PARTIALLY_PAID, self::ABANDONED],
        self::PARTIALLY_PAID => [self::PAID, self::REFUNDED],
        self::PAID => [self::REFUNDED],
        self::REFUNDED => [],
        self::ABANDONED => [],
    ];

    /**
     * Returns the raw `status`/`payment_status` (and any extra defaults, e.g.
     * clearing `expires_at`) that correspond to a logical state. Used both by
     * transition() and by controllers that create a Booking directly in a
     * given logical state (no transition to validate on brand-new rows).
     *
     * @return array<string, mixed>
     */
    public static function columns(string $state): array
    {
        return self::STATE_COLUMNS[$state]
            ?? throw new InvalidArgumentException("Unknown booking state: {$state}");
    }

    /**
     * Reverse-maps a Booking's current (status, payment_status) pair back to
     * a logical state name. Returns null for combinations that predate this
     * state machine (legacy data) — transition() treats that as "unknown,
     * allow the move" rather than blocking it.
     */
    public function currentState(Booking $booking): ?string
    {
        foreach (self::STATE_COLUMNS as $state => $columns) {
            if ($booking->status === $columns['status'] && $booking->payment_status === $columns['payment_status']) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Attempts to move a booking to the given logical state.
     *
     * - No-op (returns false) if the booking is already in that state —
     *   this is what makes both webhook handlers idempotent.
     * - Rejected (returns false, logged) if the move isn't a valid forward
     *   transition (e.g. PAID -> FAILED, or anything out of REFUNDED).
     * - Otherwise persists the target's columns merged with $extra
     *   (payment_reference, refunded_at, refund_amount, ...) and returns true.
     *
     * @param  array<string, mixed>  $extra  Additional columns to persist alongside the state
     *                                       (e.g. ['payment_reference' => $chargeId]).
     */
    public function transition(Booking $booking, string $target, array $extra = []): bool
    {
        $current = $this->currentState($booking);

        if ($current === $target) {
            Log::info('booking_state_machine.idempotent_skip', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'state' => $target,
            ]);

            return false;
        }

        if ($current !== null && ! in_array($target, self::TRANSITIONS[$current] ?? [], true)) {
            Log::warning('booking_state_machine.transition_rejected', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'from' => $current,
                'to' => $target,
            ]);

            return false;
        }

        $booking->update(array_merge(self::columns($target), $extra));

        Log::info('booking_state_machine.transitioned', [
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'from' => $current ?? 'unknown',
            'to' => $target,
        ]);

        return true;
    }
}
