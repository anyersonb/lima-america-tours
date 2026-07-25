<?php

namespace Database\Factories;

use App\Models\BlockedDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    protected $model = BlockedDate::class;

    public function definition(): array
    {
        return [
            'date' => now()->addDays($this->faker->numberBetween(1, 60))->toDateString(),
            'weekday' => null,
            'reason' => 'QA test',
        ];
    }
}
