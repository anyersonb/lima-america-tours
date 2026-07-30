<?php

namespace Database\Factories;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(5);

        return [
            'slug' => Str::slug($title).'-'.$this->faker->unique()->randomNumber(4),
            'title_es' => $title,
            'title_en' => $this->faker->sentence(5),
            'description_es' => $this->faker->paragraph(),
            'price' => $this->faker->randomElement([65, 100, 120, 200, 300, 420]),
            'price_before' => null,
            // La moneda del sitio, no un literal: el cliente la definió en USD
            // el 2026-07-29 (antes PEN) y un literal aquí obligaba a tocar cada
            // test cuando la moneda cambia. Los tests que quieren probar una
            // moneda distinta la pasan explícitamente.
            'currency' => \App\Support\Money::site(),
            'duration' => $this->faker->randomElement(['Full Day', '2 Días', '4 horas']),
            'language' => 'Español / Inglés',
            'group_type' => 'Grupal',
            'is_published' => true,
            'is_featured' => false,
            'order' => 0,
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
