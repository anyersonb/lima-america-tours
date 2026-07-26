<?php

namespace Database\Factories;

use App\Models\ContactLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactLead>
 */
class ContactLeadFactory extends Factory
{
    protected $model = ContactLead::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->numerify('9########'),
            'message' => $this->faker->paragraph(),
            'source' => 'contact_form',
            'locale' => 'es',
            'ip' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0 (Test Agent)',
            'is_read' => false,
            'is_archived' => false,
        ];
    }
}
