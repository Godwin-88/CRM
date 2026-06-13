<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorRating;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorRatingFactory extends Factory
{
    protected $model = VendorRating::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::inRandomOrder()->first()?->id ?? Vendor::factory(),
            'rated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'quality' => $this->faker->numberBetween(1, 5),
            'delivery_timeliness' => $this->faker->numberBetween(1, 5),
            'communication' => $this->faker->numberBetween(1, 5),
            'value_for_money' => $this->faker->numberBetween(1, 5),
            'notes' => $this->faker->optional()->sentence(),
            'rated_at' => $this->faker->dateTime(),
        ];
    }
}
