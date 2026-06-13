<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\PdfException;

class ContractPdfService
{
    public function generatePdf(Contract $contract, ?ContractVersion $version = null): string
    {
        $content = $this->buildMarkdown($contract);

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($content);
        $pdf->setPaper('A4');

        $pageCount = $this->getPageCount($pdf);

        if ($pageCount > 20) {
            throw new PdfException('Contract exceeds 20-page sync threshold — queueing needed.');
        }

        return $pdf->output();
    }

    private function buildMarkdown(Contract $contract): string
    {
        $html = view('contracts.pdf', [
            'contract' => $contract->load('account', 'contact', 'template.clauses', 'signatories'),
        ])->render();

        return $html;
    }

    private function getPageCount($pdf): int
    {
        try {
            return (int) $pdf->getCanvas()->get_page_number();
        } catch (\Throwable) {
            return 1;
        }
    }

    public function getStoragePath(Contract $contract, int $versionNumber): string
    {
        return 'contracts/'.$contract->account_id.'/'.$contract->id.'/v'.$versionNumber.'.pdf';
    }

    public function storePdf(Contract $contract, string $pdfContent, ?int $version = null): string
    {
        $versionNumber = $version ?? ($contract->current_version ?? 1);
        $path = $this->getStoragePath($contract, $versionNumber);

        Storage::disk('r2')->put($path, $pdfContent, [
            'visibility' => 'private',
            'ContentType' => 'application/pdf',
            'ACL' => 'private',
            'Metadata' => [
                'display_name' => $this->buildDisplayFileName($contract),
            ],
        ]);

        return $path;
    }

    public function getSignedUrl(string $path, int $minutes = 15): string
    {
        return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes($minutes));
    }

    private function buildDisplayFileName(Contract $contract): string
    {
        $accountName = Str::slug($contract->account?->name ?? 'unknown');
        $date = $contract->start_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $type = Str::slug($contract->type ?? 'contract');

        return ucfirst($type).'_'.ucfirst($accountName).'_'.$date.'.pdf';
    }
}
