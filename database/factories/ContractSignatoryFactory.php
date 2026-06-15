<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractSignatory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractSignatory>
 */
class ContractSignatoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'status' => 'pending',
            'signing_order' => 0,
        ];
    }
}
