<?php

namespace Database\Factories;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Template',
            'description' => $this->faker->paragraph,
            'type' => $this->faker->randomElement(['msa', 'nda', 'sla', 'renewal', 'upsell', 'custom']),
            'is_active' => true,
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'settings' => [],
        ];
    }
}
