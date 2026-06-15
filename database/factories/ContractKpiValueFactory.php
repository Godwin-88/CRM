<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractKpiField;
use App\Models\ContractKpiValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractKpiValue>
 */
class ContractKpiValueFactory extends Factory
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
            'contract_kpi_field_id' => ContractKpiField::factory(),
            'value' => ['data' => fake()->word()],
        ];
    }
}
