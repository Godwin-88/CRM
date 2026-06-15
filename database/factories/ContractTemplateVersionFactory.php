<?php

namespace Database\Factories;

use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractTemplateVersion>
 */
class ContractTemplateVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_template_id' => ContractTemplate::factory(),
            'version_number' => 1,
            'content' => [],
            'change_summary' => ['Initial version'],
            'created_by' => User::factory(),
        ];
    }
}
