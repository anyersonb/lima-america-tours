<?php

namespace Tests\Unit;

use App\Models\Tour;
use PHPUnit\Framework\TestCase;

/**
 * Defecto #2 de docs/qa/F7-personas.md §g (F7, cro-validator):
 *
 * El campo de precio usaba <input type="number">, que descarta la coma en
 * cuanto se teclea. Un usuario escribiendo "120,50" (como se escriben los
 * precios en Perú) terminaba con "12050" guardado sin ningún aviso — un
 * precio 100 veces mayor al real.
 *
 * Tour::normalizePriceInput() es ahora la única fuente de verdad para
 * convertir un precio "humano" (con coma o punto decimal) al float que se
 * guarda en la base de datos, y para detectar formatos inválidos.
 */
class TourPriceNormalizationTest extends TestCase
{
    public function test_comma_decimal_separator_is_normalized_to_dot(): void
    {
        $this->assertSame(120.50, Tour::normalizePriceInput('120,50'));
    }

    public function test_dot_decimal_separator_is_left_as_is(): void
    {
        $this->assertSame(120.50, Tour::normalizePriceInput('120.50'));
    }

    public function test_integer_value_without_decimals_is_accepted(): void
    {
        $this->assertSame(12050.0, Tour::normalizePriceInput('12050'));
    }

    public function test_empty_value_returns_null(): void
    {
        $this->assertNull(Tour::normalizePriceInput(''));
        $this->assertNull(Tour::normalizePriceInput(null));
    }

    public function test_malformed_value_returns_null_instead_of_guessing(): void
    {
        $this->assertNull(Tour::normalizePriceInput('120,50,00'));
        $this->assertNull(Tour::normalizePriceInput('abc'));
        $this->assertNull(Tour::normalizePriceInput('120.5.0'));
    }
}
