<?php

namespace Database\Factories;

use App\Models\DripSequence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DripSequenceFactory extends Factory
{
    protected $model = DripSequence::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Sequence',
            'description' => $this->faker->paragraph,
            'trigger' => $this->faker->randomElement(['contact_created', 'contact_stage_changed', 'manual_enrolment']),
            'status' => 'active',
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'allow_re_enrolment' => false,
        ];
    }
}
