<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = Pipeline::with('stages')->first();
        $user = User::first();
        
        if (!$pipeline) {
            return;
        }
        
        $contacts = Contact::limit(2)->get();
        $accounts = Account::limit(2)->get();
        
        if ($contacts->isEmpty() || $accounts->isEmpty()) {
            return;
        }

        foreach ($contacts as $index => $contact) {
            $account = $accounts[$index] ?? $accounts->first();
            $firstStage = $pipeline->stages->first();

            Deal::create([
                'title' => "Deal for {$contact->first_name} {$contact->last_name}",
                'contact_id' => $contact->id,
                'account_id' => $account->id,
                'pipeline_id' => $pipeline->id,
                'stage' => $firstStage?->name ?? 'lead',
                'value' => rand(1000, 100000) / 100,
                'currency' => 'USD',
                'probability' => $firstStage?->probability ?? 10,
                'owner_id' => $user?->id,
            ]);
        }
    }
}