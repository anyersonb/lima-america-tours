<?php

namespace Tests\Unit;

use App\Models\Tour;
use PHPUnit\Framework\TestCase;

/**
 * Hallazgo #8 de docs/qa/ficha-tour.md (F2, cro-validator):
 *
 * El formulario de Tour permitía guardar price=0 sin validación mínima.
 * Combinado con price_before > 0, el cálculo de descuento (duplicado antes
 * en el Placeholder del admin y en tours/show.blade.php) marcaba una
 * "OFERTA ESPECIAL -100%" — matemáticamente cierto pero engañoso como dato
 * de negocio (nadie regala un tour). El mismo cálculo tampoco contemplaba
 * price_before <= price como "sin oferta" de forma consistente.
 *
 * Tour::offerDiscountPercent() es ahora la única fuente de verdad: prueba
 * unitaria pura (sin BD) de sus reglas.
 */
class TourOfferDiscountTest extends TestCase
{
    public function test_price_zero_never_counts_as_an_offer(): void
    {
        $this->assertNull(Tour::offerDiscountPercent(0, 100));
        $this->assertNull(Tour::offerDiscountPercent(0, 0));
    }

    public function test_price_before_not_greater_than_price_never_counts_as_an_offer(): void
    {
        $this->assertNull(Tour::offerDiscountPercent(100, 100)); // equal
        $this->assertNull(Tour::offerDiscountPercent(150, 100)); // price_before lower than price
    }

    public function test_empty_price_before_never_counts_as_an_offer(): void
    {
        $this->assertNull(Tour::offerDiscountPercent(50, 0));
    }

    public function test_valid_offer_calculates_the_correct_percentage(): void
    {
        $this->assertSame(50, Tour::offerDiscountPercent(50, 100));
        $this->assertSame(25, Tour::offerDiscountPercent(75, 100));
    }
}
