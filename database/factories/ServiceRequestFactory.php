<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catalog_item_id' => ServiceCatalogItem::factory(),
            'requester_id' => User::factory(),
            'contact_id' => Contact::factory(),
            'account_id' => null,
            'channel' => 'api',
            'source_identifier' => fake()->unique()->uuid(),
            'status' => ServiceRequest::STATUS_SUBMITTED,
            'priority' => 'medium',
            'assigned_to' => null,
            'metadata' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceRequest::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceRequest::STATUS_CLOSED,
            'closed_at' => now(),
            'closure_reason' => 'Completed and closed',
        ]);
    }
}
