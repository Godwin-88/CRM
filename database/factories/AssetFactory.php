<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word().' Asset',
            'type' => $this->faker->randomElement(['hardware', 'software', 'vehicle', 'furniture', 'custom']),
            'identifier' => $this->faker->optional()->regexify('[A-Z0-9]{8}'),
            'purchase_date' => $this->faker->optional()->date(),
            'purchase_cost' => $this->faker->optional()->randomFloat(2, 100, 10000),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['available', 'assigned', 'under_maintenance', 'disposed']),
            'useful_life_years' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }
}
