<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            DataClassificationSeeder::class,
            RateLimitSeeder::class,
            PipelineAndStageSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create an account for linking contacts
        $account = Account::create([
            'name' => 'Acme Corporation',
            'type' => 'customer',
            'industry' => 'Technology',
            'status' => 'active',
            'website' => 'https://acme.example.com',
            'city' => 'San Francisco',
            'state' => 'CA',
            'country' => 'USA',
            'annual_revenue' => 5000000,
            'employee_count' => 100,
            'account_manager_id' => $user->id,
        ]);

        // Create second account
        $account2 = Account::create([
            'name' => 'Globex Inc',
            'type' => 'prospect',
            'industry' => 'Manufacturing',
            'status' => 'active',
            'website' => 'https://globex.example.com',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'USA',
            'annual_revenue' => 2500000,
            'employee_count' => 50,
            'account_manager_id' => $user->id,
        ]);

        // Create contacts linked to accounts
        $contact1 = Contact::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1-555-0101',
            'type' => 'customer',
            'status' => 'active',
            'source' => 'website',
            'owner_id' => $user->id,
            'account_id' => $account->id,
        ]);

        $contact2 = Contact::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@acme.example.com',
            'phone' => '+1-555-0102',
            'type' => 'lead',
            'status' => 'active',
            'source' => 'referral',
            'owner_id' => $user->id,
            'account_id' => $account->id,
        ]);

        // Run deal seeder after accounts/contacts exist
        $this->call([
            DealSeeder::class,
        ]);
    }
}
