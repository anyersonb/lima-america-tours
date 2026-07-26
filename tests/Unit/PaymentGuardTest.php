<?php

namespace Tests\Unit;

use App\Exceptions\RealChargeBlockedException;
use App\Services\PaymentGuard;
use Tests\TestCase;

/**
 * "Guarda anti-cobro-real": PaymentGuard::assertChargeAllowed() must be the
 * single choke-point that stops a real (LIVE) charge from firing while we
 * validate the sandbox flow on a non-production environment. See
 * app/Services/PaymentGuard.php for the rule this enforces.
 */
class PaymentGuardTest extends TestCase
{
    public function test_blocks_culqi_live_key_on_a_non_production_environment(): void
    {
        config(['payments.live' => false]);

        $this->assertFalse(app()->environment('production'));

        $this->expectException(RealChargeBlockedException::class);

        PaymentGuard::assertChargeAllowed('culqi', 'sk_live_abc123');
    }

    public function test_allows_culqi_test_key_regardless_of_environment(): void
    {
        config(['payments.live' => false]);

        PaymentGuard::assertChargeAllowed('culqi', 'sk_test_abc123');

        $this->addToAssertionCount(1); // no exception thrown = pass
    }

    public function test_blocks_paypal_live_mode_on_a_non_production_environment(): void
    {
        config(['payments.live' => false]);

        $this->expectException(RealChargeBlockedException::class);

        PaymentGuard::assertChargeAllowed('paypal', 'live');
    }

    public function test_allows_paypal_sandbox_mode(): void
    {
        config(['payments.live' => false]);

        PaymentGuard::assertChargeAllowed('paypal', 'sandbox');

        $this->addToAssertionCount(1); // no exception thrown = pass
    }

    /**
     * PAYMENTS_LIVE=true alone is not enough — the environment must ALSO be
     * "production". This proves the guard uses AND, not OR.
     */
    public function test_blocks_live_key_even_with_payments_live_true_when_not_in_production(): void
    {
        config(['payments.live' => true]);

        $this->assertFalse(app()->environment('production'));

        $this->expectException(RealChargeBlockedException::class);

        PaymentGuard::assertChargeAllowed('culqi', 'sk_live_abc123');
    }

    /**
     * The only combination that lets a LIVE charge through: PAYMENTS_LIVE=true
     * AND app()->environment() === 'production'.
     */
    public function test_allows_live_key_when_payments_live_true_and_environment_is_production(): void
    {
        config(['payments.live' => true]);
        app()->instance('env', 'production');

        PaymentGuard::assertChargeAllowed('culqi', 'sk_live_abc123');
        PaymentGuard::assertChargeAllowed('paypal', 'live');

        $this->addToAssertionCount(2); // no exception thrown = pass
    }

    public function test_blocked_exception_message_explains_the_reason_in_spanish(): void
    {
        config(['payments.live' => false]);

        try {
            PaymentGuard::assertChargeAllowed('culqi', 'sk_live_abc123');
            $this->fail('Expected RealChargeBlockedException was not thrown.');
        } catch (RealChargeBlockedException $e) {
            $this->assertStringContainsString('Cobro real bloqueado', $e->getMessage());
            $this->assertStringContainsString('LIVE', $e->getMessage());
        }
    }
}
