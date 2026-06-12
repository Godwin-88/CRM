<?php

namespace Database\Seeders;

use App\Models\WinLossReason;
use Illuminate\Database\Seeder;

class WinLossReasonsSeeder extends Seeder
{
    public function run(): void
    {
        $wonReasons = [
            'Competitor pricing error',
            'Product features matched requirements',
            'Relationship built trust',
            'Timeline requirements met',
            'Other (specify)',
        ];

        $lostReasons = [
            'Competitor pricing',
            'Product missing features',
            'No budget available',
            'Decision delayed',
            'Chose internal solution',
            'Other (specify)',
        ];

        foreach ($wonReasons as $label) {
            WinLossReason::create(['type' => 'won', 'label' => $label]);
        }

        foreach ($lostReasons as $label) {
            WinLossReason::create(['type' => 'lost', 'label' => $label]);
        }
    }
}