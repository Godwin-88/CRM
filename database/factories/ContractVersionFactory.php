<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractVersion>
 */
class ContractVersionFactory extends Factory
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
            'version_number' => 1,
            'status' => 'draft',
            'created_by' => User::factory(),
            'variables' => [],
            'selected_clauses' => [],
        ];
    }
}
