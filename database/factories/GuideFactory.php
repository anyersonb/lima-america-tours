<?php

namespace Database\Factories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    protected $model = Guide::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role_es' => 'Guía turístico',
            'role_en' => 'Tour guide',
            'role_pt' => 'Guia turístico',
            'photo' => null,
            'bio_es' => $this->faker->paragraph(),
            'bio_en' => $this->faker->paragraph(),
            'bio_pt' => $this->faker->paragraph(),
            'languages' => ['Español', 'Inglés'],
            'years_experience' => $this->faker->numberBetween(1, 15),
            'order' => 0,
            'is_active' => true,
        ];
    }
}
