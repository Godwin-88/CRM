<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::inRandomOrder()->first()?->id ?? Account::factory(),
            'contact_id' => Contact::inRandomOrder()->first()?->id ?? Contact::factory(),
            'invoice_number' => 'INV-'.$this->faker->year().'-'.$this->faker->unique()->numberBetween(1, 9999),
            'status' => $this->faker->randomElement(['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled']),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'KES']),
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'total_tax' => $this->faker->randomFloat(2, 0, 1000),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'due_date' => $this->faker->date(),
            'sent_at' => $this->faker->optional()->dateTime(),
            'paid_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
