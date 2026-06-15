<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'type' => fake()->randomElement(['lead', 'customer', 'partner']),
            'status' => fake()->randomElement(['active', 'inactive']),
            'owner_id' => User::factory(),
            'clv_score' => fake()->randomFloat(2, 0, 100),
            'loyalty_tier' => fake()->randomElement(['bronze', 'silver', 'gold', 'platinum']),
        ];
    }
}
