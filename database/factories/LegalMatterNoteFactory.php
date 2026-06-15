<?php

namespace Database\Factories;

use App\Models\LegalMatter;
use App\Models\LegalMatterNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalMatterNote>
 */
class LegalMatterNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legal_matter_id' => LegalMatter::factory(),
            'created_by' => User::factory(),
            'body' => fake()->paragraph(),
            'type' => fake()->randomElement(['note', 'call', 'email', 'meeting']),
            'attachments' => [],
        ];
    }
}
