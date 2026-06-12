<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineAndStageSeeder extends Seeder
{
    public function run(): void
    {
        // Create Sales Pipeline
        $salesPipeline = Pipeline::create([
            'name' => 'Sales Pipeline',
            'description' => 'Default sales pipeline for opportunity management',
            'is_default' => true,
            'is_active' => true,
        ]);

        $salesStages = [
            ['name' => 'lead', 'probability' => 10, 'position' => 1],
            ['name' => 'qualified', 'probability' => 20, 'position' => 2],
            ['name' => 'proposal', 'probability' => 40, 'position' => 3],
            ['name' => 'negotiation', 'probability' => 60, 'position' => 4],
            ['name' => 'closed_won', 'probability' => 100, 'position' => 5],
            ['name' => 'closed_lost', 'probability' => 0, 'position' => 6],
        ];

        foreach ($salesStages as $stage) {
            PipelineStage::create([
                'pipeline_id' => $salesPipeline->id,
                'name' => $stage['name'],
                'probability' => $stage['probability'],
                'position' => $stage['position'],
            ]);
        }

        // Create Marketing Pipeline
        $marketingPipeline = Pipeline::create([
            'name' => 'Marketing Qualified',
            'description' => 'Marketing lead nurturing pipeline',
            'is_default' => false,
            'is_active' => true,
        ]);

        $marketingStages = [
            ['name' => 'mql', 'probability' => 5, 'position' => 1],
            ['name' => 'sql', 'probability' => 30, 'position' => 2],
            ['name' => 'sales_ready', 'probability' => 60, 'position' => 3],
            ['name' => 'converted', 'probability' => 100, 'position' => 4],
            ['name' => 'disqualified', 'probability' => 0, 'position' => 5],
        ];

        foreach ($marketingStages as $stage) {
            PipelineStage::create([
                'pipeline_id' => $marketingPipeline->id,
                'name' => $stage['name'],
                'probability' => $stage['probability'],
                'position' => $stage['position'],
            ]);
        }
    }
}