<?php

namespace Database\Factories;

use App\Models\FormSchema;
use App\Models\FormSchemaVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSchemaVersion>
 */
class FormSchemaVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'form_schema_id' => FormSchema::factory(),
            'version_number' => 1,
            'fields' => [
                ['name' => 'issue_description', 'label' => 'Issue description', 'type' => 'textarea', 'required' => true],
            ],
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
