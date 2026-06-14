<?php

namespace Database\Seeders;

use App\Models\BankingRelationship;
use App\Models\Contact;
use App\Models\DataClassification;
use App\Models\Invoice;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DataClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            // Contact PII fields
            ['model_type' => Contact::class, 'field_name' => 'email', 'sensitivity' => 'pii'],
            ['model_type' => Contact::class, 'field_name' => 'phone', 'sensitivity' => 'pii'],
            ['model_type' => Contact::class, 'field_name' => 'national_id', 'sensitivity' => 'pii'],

            // Invoice financial fields
            ['model_type' => Invoice::class, 'field_name' => 'total', 'sensitivity' => 'financial'],
            ['model_type' => Invoice::class, 'field_name' => 'bank_details', 'sensitivity' => 'financial'],

            // Vendor financial fields
            ['model_type' => Vendor::class, 'field_name' => 'bank_details', 'sensitivity' => 'financial'],

            // Banking relationship confidential
            ['model_type' => BankingRelationship::class, 'field_name' => 'notes', 'sensitivity' => 'confidential'],
        ];

        foreach ($classifications as $classification) {
            DataClassification::create($classification);
        }
    }
}
