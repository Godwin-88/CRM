<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractMilestone>
 */
class ContractMilestoneFactory extends Factory
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
            'name' => fake()->sentence(),
            'due_date' => fake()->date(),
            'status' => 'pending',
        ];
    }
}
