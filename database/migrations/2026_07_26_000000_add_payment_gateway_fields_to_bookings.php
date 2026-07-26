<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 de la pasarela de pago (Culqi + PayPal) — docs/pagos/PLAN-PASARELAS.md §4.
 *
 * Aditiva, no rompe columnas existentes:
 *  - expires_at:     hold de cupo mientras el pago está en curso (Culqi pre-charge,
 *                    §3.2). Se limpia (null) cuando la reserva pasa a pagado/parcial.
 *  - refunded_at:    fecha del reembolso (webhook o acción admin, §6).
 *  - refund_amount:  monto reembolsado (total o parcial).
 *
 * `status`/`payment_status` siguen siendo columnas string libres (sin ENUM de BD):
 * a los valores existentes (pending/confirmed/paid/failed) se suman de forma
 * puramente lógica — sin migración de esquema — `cancelled`/`refunded` (status) y
 * `expired`/`refunded`/`partially_paid` (payment_status), documentados y validados
 * por App\Services\BookingStateMachine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('payment_reference');
            $table->timestamp('refunded_at')->nullable()->after('expires_at');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'refunded_at', 'refund_amount']);
        });
    }
};
