<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'country' => $this->faker->country(),
            'quote_es' => $this->faker->paragraph(),
            'rating' => $this->faker->randomFloat(1, 4, 5),
            'source' => 'Google',
            'is_featured' => false,
            'is_active' => true,
            'order' => 0,
        ];
    }
}
