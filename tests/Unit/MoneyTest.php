<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

/**
 * Formato de precios por moneda. Lima View/América Tours pasó a operar en
 * soles (PEN) tras la migración de WordPress (docs/pagos/PLAN-PASARELAS.md
 * §13); el front hardcodeaba "$" para todos los precios, mostrando "$360"
 * para un precio en soles. Money::format resuelve el símbolo por moneda.
 */
class MoneyTest extends TestCase
{
    public function test_formats_pen_with_sol_symbol(): void
    {
        $this->assertSame('S/ 360', Money::format(360, 'PEN'));
        $this->assertSame('S/ 1,234', Money::format(1234, 'PEN'));
    }

    public function test_formats_usd_with_dollar_symbol(): void
    {
        $this->assertSame('$360', Money::format(360, 'USD'));
        $this->assertSame('$1,235', Money::format(1234.5, 'USD'));
    }

    public function test_formats_eur_with_euro_symbol(): void
    {
        $this->assertSame('€1,234', Money::format(1234, 'EUR'));
    }

    public function test_unknown_currency_falls_back_to_code_and_space(): void
    {
        $this->assertSame('COP 1,234', Money::format(1234, 'COP'));
    }

    public function test_null_or_empty_currency_defaults_to_usd(): void
    {
        $this->assertSame('$360', Money::format(360, null));
        $this->assertSame('$360', Money::format(360, ''));
    }

    public function test_respects_decimals_argument(): void
    {
        $this->assertSame('S/ 360.00', Money::format(360, 'PEN', 2));
        $this->assertSame('$1,234.50', Money::format(1234.5, 'USD', 2));
    }

    public function test_accepts_numeric_strings(): void
    {
        $this->assertSame('S/ 720', Money::format('720', 'PEN'));
    }

    public function test_prefix_returns_symbol_without_amount(): void
    {
        $this->assertSame('S/ ', Money::prefix('PEN'));
        $this->assertSame('$', Money::prefix('USD'));
        $this->assertSame('$', Money::prefix(null));
        $this->assertSame('COP ', Money::prefix('COP'));
    }
}
