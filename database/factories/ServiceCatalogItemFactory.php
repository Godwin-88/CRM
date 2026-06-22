<?php

namespace Database\Factories;

use App\Models\ServiceCatalogItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCatalogItem>
 */
class ServiceCatalogItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'customer_instructions' => fake()->paragraph(),
            'default_priority' => 'medium',
            'required_documents' => [],
            'automation_config' => [],
            'portal_visible' => true,
            'email_visible' => false,
            'kiosk_visible' => false,
            'api_visible' => true,
            'is_active' => true,
            'is_agent_only' => false,
            'created_by_id' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
    }

    public function agentOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_agent_only' => true,
            'portal_visible' => false,
        ]);
    }
}
