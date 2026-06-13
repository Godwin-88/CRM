<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'po_number' => 'PO-'.$this->faker->year().'-'.$this->faker->unique()->numberBetween(1, 9999),
            'vendor_id' => Vendor::inRandomOrder()->first()?->id ?? Vendor::factory(),
            'status' => $this->faker->randomElement(['draft', 'submitted', 'approved', 'partially_received', 'received', 'cancelled']),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'KES']),
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'total_tax' => $this->faker->randomFloat(2, 0, 1000),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'required_by_date' => $this->faker->date(),
        ];
    }
}
