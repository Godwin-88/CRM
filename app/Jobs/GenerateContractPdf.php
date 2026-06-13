<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Services\ContractGenerationService;
use App\Services\ContractPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Activitylog\Facades\ActivityLog;

class GenerateContractPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    public function __construct(
        public ?int $contractId,
        public ?int $userId = null,
        public array $options = [],
    ) {}

    public function handle(
        ContractGenerationService $generationService,
        ContractPdfService $pdfService
    ): void {
        $contract = Contract::findOrFail($this->contractId);
        $contract->load('template', 'account', 'contact');

        $fileSize = 0;
        $documentPath = null;

        try {
            $selectedClauses = $this->options['selected_clauses'] ?? [];
            $variables = $this->options['variables'] ?? [];

            $version = $generationService->generateFromTemplate($contract, $selectedClauses, $variables);

            $userId = $this->userId ?? auth()->id();
            if ($userId) {
                activity()
                    ->performedOn($contract)
                    ->causedBy(User::find($userId))
                    ->withProperties(['version' => $version->version_number])
                    ->log('contract_pdf_job_completed');
            }
        } catch (\Throwable $e) {
            ActivityLog::performedOn($contract)->withProperties([
                'error' => $e->getMessage(),
            ])->log('contract_pdf_failed');

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $contract = Contract::find($this->contractId);
        if ($contract) {
            $contract->update(['status' => Contract::STATUS_DRAFT]);
            activity()
                ->performedOn($contract)
                ->withProperties(['error' => $e->getMessage()])
                ->log('contract_pdf_failed');
        }
    }
}
