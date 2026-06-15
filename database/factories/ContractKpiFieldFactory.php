<?php

namespace Database\Factories;

use App\Models\ContractKpiField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractKpiField>
 */
class ContractKpiFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_type' => fake()->randomElement(['msa', 'nda', 'sla', 'renewal', 'upsell', 'custom']),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['number', 'text', 'boolean', 'date']),
            'is_required' => false,
            'is_active' => true,
        ];
    }
}
