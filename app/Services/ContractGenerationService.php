<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractVersion;

class ContractGenerationService
{
    public function __construct(
        protected ContractPdfService $pdfService,
        protected ContractLifecycleService $lifecycleService,
    ) {}

    public function generateFromTemplate(Contract $contract, array $selectedClauses = [], array $variables = []): ContractVersion
    {
        $template = $contract->template;
        $contract->load('account', 'contact');

        $pdfContent = $this->pdfService->generatePdf($contract);
        $storagePath = $this->pdfService->storePdf($contract, $pdfContent, $contract->current_version);

        $version = ContractVersion::create([
            'contract_id' => $contract->id,
            'version_number' => $contract->current_version,
            'status' => $contract->status,
            'document_path' => $storagePath,
            'page_count' => 1,
            'file_size' => strlen($pdfContent),
            'created_by' => auth()->id(),
            'variables' => $variables,
            'selected_clauses' => $selectedClauses,
        ]);

        $contract->update(['document_path' => $storagePath]);

        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'version' => $version->version_number,
                'page_count' => $version->page_count,
                'file_size' => $version->file_size,
            ])
            ->log('contract_pdf_generated');

        return $version;
    }
}
