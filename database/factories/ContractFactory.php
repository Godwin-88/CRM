<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true) . ' Agreement',
            'contact_id' => Contact::inRandomOrder()->first()?->id ?? Contact::factory(),
            'account_id' => Account::inRandomOrder()->first()?->id ?? Account::factory(),
            'type' => $this->faker->randomElement(['msa', 'nda', 'sla', 'renewal', 'upsell', 'custom']),
            'status' => $this->faker->randomElement(['draft', 'sent', 'signed', 'active', 'expiring', 'expired', 'declined', 'terminated']),
            'value' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => 'USD',
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'document_path' => null,
            'e_signature_status' => null,
            'template_id' => null,
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'account_manager_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'suppress_reminders' => false,
            'current_version' => 1,
        ];
    }
}