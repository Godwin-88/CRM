<?php

namespace Database\Factories;

use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseRecord>
 */
class CaseRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_number' => 'CASE-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('????????')),
            'title' => fake()->sentence(),
            'type' => CaseRecord::TYPE_SERVICE_DELIVERY,
            'priority' => 'medium',
            'status' => CaseRecord::STATUS_NEW,
            'owner_id' => User::factory(),
            'signoff_required' => false,
            'metadata' => [],
        ];
    }

    public function pendingSignoff(): static
    {
        return $this->state(fn (array $attributes) => [
            'signoff_required' => true,
            'signoff_status' => 'pending',
            'signoff_due_at' => now()->addDay(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CaseRecord::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }
}
