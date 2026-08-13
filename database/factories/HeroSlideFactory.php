<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    protected $model = HeroSlide::class;

    public function definition(): array
    {
        return [
            'image' => 'home/'.$this->faker->uuid().'.webp',
            'alt_es' => $this->faker->sentence(),
            'alt_en' => null,
            'alt_pt' => null,
            'order' => 0,
            'is_active' => true,
        ];
    }
}
