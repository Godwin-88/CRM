<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'category' => $this->faker->randomElement(['goods', 'services', 'both']),
            'primary_contact_name' => $this->faker->name(),
            'primary_contact_email' => $this->faker->email(),
            'primary_contact_phone' => $this->faker->phoneNumber(),
            'registration_number' => $this->faker->optional()->regexify('[A-Z0-9]{10}'),
            'tax_identification_number' => $this->faker->optional()->regexify('[A-Z0-9]{10}'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
