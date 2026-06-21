<?php

namespace Database\Factories;

use App\Models\FormSchema;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSchema>
 */
class FormSchemaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'created_by_id' => User::factory(),
            'is_active' => true,
        ];
    }
}
