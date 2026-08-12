<?php

namespace Tests\Feature;

use App\Models\Guide;
use Database\Seeders\GuideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los 4 guías reales confirmados el 2026-08-10 (mockup 02-nosotros-parte1).
 * Idempotente por diseño: re-sembrar nunca duplica filas.
 */
class GuideSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_exactly_the_four_confirmed_guides(): void
    {
        (new GuideSeeder)->run();

        $this->assertSame(4, Guide::count());
        $this->assertSame(
            ['Samira', 'Nikki', 'Arturo', 'Augusto'],
            Guide::ordered()->pluck('name')->all()
        );
    }

    public function test_running_it_twice_does_not_duplicate_rows(): void
    {
        (new GuideSeeder)->run();
        (new GuideSeeder)->run();

        $this->assertSame(4, Guide::count());
    }

    /**
     * Ninguna foto de stock ni "adivinada": docs/rebrand/CONTENIDO-REAL-PRODUCCION.md
     * §3.1 tiene retratos reales del equipo, pero el propio documento no
     * confirma la correspondencia nombre↔foto. Sembrar una foto ahí sería
     * exactamente la regla que este lote corrige (nunca la cara de otra
     * persona).
     */
    public function test_none_of_the_seeded_guides_has_a_photo(): void
    {
        (new GuideSeeder)->run();

        $this->assertSame(0, Guide::whereNotNull('photo')->count());
    }

    public function test_samira_has_the_verifiable_highlight_and_the_others_do_not(): void
    {
        (new GuideSeeder)->run();

        $samira = Guide::where('name', 'Samira')->firstOrFail();
        $this->assertStringContainsString('15 de 20', $samira->highlight_es);

        $others = Guide::where('name', '!=', 'Samira')->get();
        foreach ($others as $guide) {
            $this->assertNull($guide->highlight_es);
        }
    }

    public function test_all_seeded_guides_are_active(): void
    {
        (new GuideSeeder)->run();

        $this->assertSame(4, Guide::active()->count());
    }
}
