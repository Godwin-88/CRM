<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['customer', 'partner', 'vendor', 'prospect']),
            'industry' => fake()->randomElement(['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail']),
            'status' => fake()->randomElement(['active', 'inactive', 'on_hold']),
            'website' => fake()->url(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'annual_revenue' => fake()->randomFloat(2, 10000, 1000000),
            'employee_count' => fake()->numberBetween(1, 5000),
            'account_manager_id' => User::factory(),
        ];
    }
}
