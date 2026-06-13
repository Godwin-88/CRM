<?php

namespace Database\Factories;

use App\Models\LegalMatter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LegalMatterFactory extends Factory
{
    protected $model = LegalMatter::class;

    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['open', 'in_progress', 'pending_external', 'resolved', 'closed']),
            'type' => $this->faker->randomElement(['dispute', 'correspondence', 'regulatory', 'advisory', 'custom']),
            'assigned_to' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'resolution_notes' => null,
        ];
    }
}
