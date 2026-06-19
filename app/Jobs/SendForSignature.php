<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Models\ContractSignatory;
use App\Services\ContractGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendForSignature implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 3;

    public function __construct(
        public int $contractId,
        public array $signatories = [],
        public ?int $userId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public function handle(): void
    {
        $contract = Contract::with('template.clauses')->findOrFail($this->contractId);
        $contract->load('account', 'contact');

        try {
            $generator = app(ContractGenerationService::class);
            $version = $generator->generateFromTemplate($contract, [], []);

            foreach ($this->signatories as $signatory) {
                $signingUrl = $this->buildSigningUrl($contract, $signatory['email']);

                ContractSignatory::updateOrCreate([
                    'contract_id' => $contract->id,
                    'email' => $signatory['email'],
                    'contract_version_id' => $version->id,
                ], [
                    'user_id' => $signatory['user_id'] ?? null,
                    'name' => $signatory['name'],
                    'role' => $signatory['role'] ?? 'counterparty',
                    'status' => ContractSignatory::STATUS_PENDING,
                    'provider' => 'internal',
                    'signing_url' => $signingUrl,
                    'signing_token' => Str::random(64),
                    'signing_order' => $signatory['order'] ?? 1,
                    'is_sequential' => $signatory['is_sequential'] ?? true,
                    'ip_address' => $this->ipAddress,
                    'user_agent' => $this->userAgent,
                ]);
            }

            $contract->update([
                'status' => Contract::STATUS_SENT,
                'e_signature_status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            activity()
                ->performedOn($contract)
                ->withProperties(['error' => $e->getMessage()])
                ->log('send_for_signature_failed');
            throw $e;
        }
    }

    private function buildSigningUrl(Contract $contract, string $email): string
    {
        $token = Str::random(64);
        $path = 'contracts/'.$contract->account_id.'/'.$contract->id.'/v'.$contract->current_version.'.pdf';
        $signedPdfUrl = Storage::disk('r2')->temporaryUrl($path, now()->addHours(2));

        return route('signing.view', ['contract' => $contract->id, 'token' => $token, 'email' => urlencode($email)]);
    }
}
