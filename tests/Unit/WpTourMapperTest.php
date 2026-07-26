<?php

namespace Tests\Unit;

use App\Support\WpTourMapper;
use PHPUnit\Framework\TestCase;

/**
 * Mapeo de taxonomías de WordPress a campos de `tours`. Los tours reales del
 * censo (docs/data/RECONCILIACION-WP.md): viaje-destacado="Si" en 4 tours;
 * lugar con términos Lima/Callao/Nazca/Paracas/Cusco.
 */
class WpTourMapperTest extends TestCase
{
    public function test_is_featured_true_only_when_si(): void
    {
        $this->assertTrue(WpTourMapper::isFeatured(['Si']));
        $this->assertTrue(WpTourMapper::isFeatured(['si']));
        $this->assertFalse(WpTourMapper::isFeatured(['No']));
        $this->assertFalse(WpTourMapper::isFeatured([]));
    }

    public function test_region_key_from_lugar_maps_callao_to_lima_and_nazca_paracas_to_ica(): void
    {
        $this->assertSame('Lima', WpTourMapper::regionKeyFromLugar(['Lima']));
        $this->assertSame('Lima', WpTourMapper::regionKeyFromLugar(['Callao']));
        $this->assertSame('Ica', WpTourMapper::regionKeyFromLugar(['Nazca']));
        $this->assertSame('Ica', WpTourMapper::regionKeyFromLugar(['Paracas']));
        $this->assertSame('Cusco', WpTourMapper::regionKeyFromLugar(['Cusco']));
    }

    public function test_region_key_null_when_unknown_or_empty(): void
    {
        $this->assertNull(WpTourMapper::regionKeyFromLugar([]));
        $this->assertNull(WpTourMapper::regionKeyFromLugar(['Marte']));
    }
}
