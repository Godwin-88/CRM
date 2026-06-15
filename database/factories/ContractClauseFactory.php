<?php

namespace Database\Factories;

use App\Models\ContractClause;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractClause>
 */
class ContractClauseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'body' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['legal', 'business', 'technical']),
            'type' => fake()->randomElement(['standard', 'confidentiality', 'termination', 'liability']),
            'is_global' => fake()->boolean(),
            'is_active' => true,
            'variables' => [],
        ];
    }
}
