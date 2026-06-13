<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::inRandomOrder()->first()?->id ?? Invoice::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'card', 'mobile_money', 'cash', 'other']),
            'reference_number' => $this->faker->optional()->regexify('[A-Z0-9]{10}'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
