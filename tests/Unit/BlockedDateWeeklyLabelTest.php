<?php

namespace Tests\Unit;

use App\Filament\Resources\BlockedDateResource;
use PHPUnit\Framework\TestCase;

/**
 * Hallazgo #6 de docs/qa/panel-filament.md (F2, cro-validator):
 *
 * La columna "Bloqueo" del listado de BlockedDate, para bloqueos recurrentes
 * de tipo "Día de la semana", concatenaba `"Todos los {$day}s"` con el
 * nombre del día ya en su forma correcta ("Lunes", "Martes"…), produciendo
 * un doble plural: "Todos los Luness", "Todos los Martess", etc. Solo
 * Domingo/Sábado coincidían por accidente ("Domingos"/"Sábados").
 *
 * `BlockedDateResource::weeklyBlockLabel()` fue extraído del closure de la
 * tabla precisamente para poder probarlo sin Livewire/HTTP.
 */
class BlockedDateWeeklyLabelTest extends TestCase
{
    /**
     * @dataProvider weekdayProvider
     */
    public function test_weekly_block_label_is_correctly_pluralized(int $weekday, string $expected): void
    {
        $this->assertSame($expected, BlockedDateResource::weeklyBlockLabel($weekday));
    }

    public static function weekdayProvider(): array
    {
        return [
            'domingo' => [0, 'Todos los domingos'],
            'lunes' => [1, 'Todos los lunes'],
            'martes' => [2, 'Todos los martes'],
            'miercoles' => [3, 'Todos los miércoles'],
            'jueves' => [4, 'Todos los jueves'],
            'viernes' => [5, 'Todos los viernes'],
            'sabado' => [6, 'Todos los sábados'],
        ];
    }

    public function test_weekly_block_label_never_double_pluralizes(): void
    {
        foreach (range(0, 6) as $weekday) {
            $label = BlockedDateResource::weeklyBlockLabel($weekday);
            $this->assertDoesNotMatchRegularExpression('/(lunes|martes|miércoles|jueves|viernes)s\b/u', $label);
            $this->assertStringNotContainsString('Luness', $label);
        }
    }

    public function test_weekly_block_label_falls_back_gracefully_for_unknown_index(): void
    {
        $this->assertSame('Todos los día 9', BlockedDateResource::weeklyBlockLabel(9));
    }
}
