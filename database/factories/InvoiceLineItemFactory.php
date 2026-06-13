<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceLineItemFactory extends Factory
{
    protected $model = InvoiceLineItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $taxRate = $this->faker->randomFloat(4, 0, 20);
        $lineTotal = $quantity * $unitPrice;
        $taxAmount = $lineTotal * ($taxRate / 100);

        return [
            'invoice_id' => Invoice::inRandomOrder()->first()?->id ?? Invoice::factory(),
            'description' => $this->faker->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
            'tax_amount' => $taxAmount,
        ];
    }
}
