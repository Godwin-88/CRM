<?php

namespace Database\Factories;

use App\Models\CampaignTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignTemplateFactory extends Factory
{
    protected $model = CampaignTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'subject' => $this->faker->sentence,
            'html_content' => '<div>' . $this->faker->paragraph . '</div>',
            'status' => 'draft',
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'version' => 1,
            'is_active' => true,
        ];
    }
}
