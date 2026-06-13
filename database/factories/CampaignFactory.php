<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Campaign',
            'description' => $this->faker->paragraph,
            'type' => $this->faker->randomElement(['email', 'sms', 'multi_channel']),
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'sending', 'sent']),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'throttle_emails_per_hour' => 5000,
            'throttle_sms_per_hour' => 1000,
            'optimize_send_time' => false,
        ];
    }
}
