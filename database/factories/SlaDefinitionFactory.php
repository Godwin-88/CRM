<?php

namespace Database\Factories;

use App\Models\SlaDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaDefinition>
 */
class SlaDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'priority' => 'medium',
            'first_response_time_business_hours' => 4,
            'resolution_time_business_hours' => 24,
            'acknowledgement_time_business_hours' => 2,
            'review_time_business_hours' => 8,
            'next_action_time_business_hours' => 12,
            'completion_time_business_hours' => 24,
            'triage_time_business_hours' => 4,
            'investigation_update_time_business_hours' => 12,
            'resolution_proposal_time_business_hours' => 24,
            'closure_signoff_time_business_hours' => 12,
            'milestone_definitions' => [],
            'is_default' => false,
        ];
    }

    public function defaultSla(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
