<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Console\Command;

class AnonymizeDeletedContacts extends Command
{
    protected $signature = 'contacts:anonymize {--contact= : Specific contact ID to anonymize}';

    protected $description = 'Anonymize soft-deleted contacts past retention period';

    public function handle(): int
    {
        $retentionDays = config('security.data_retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        $query = Contact::onlyTrashed()->where('deleted_at', '<=', $cutoffDate);

        if ($contactId = $this->option('contact')) {
            $query->where('id', $contactId);
        }

        $contacts = $query->get();

        $this->info("Found {$contacts->count()} contacts to anonymize.");

        foreach ($contacts as $contact) {
            $this->anonymizeContact($contact);
        }

        $this->info('Anonymization complete.');

        return 0;
    }

    private function anonymizeContact(Contact $contact): void
    {
        $contact->update([
            'first_name' => 'Anonymised-'.substr(md5($contact->id), 0, 8),
            'last_name' => 'Customer',
            'email' => null,
            'phone' => null,
            'national_id' => null,
            'marketing_consent' => false,
            'data_processing_consent' => false,
        ]);

        Invoice::where('contact_id', $contact->id)
            ->update(['contact_name' => 'Anonymised Customer']);

        Contract::where('contact_id', $contact->id)
            ->update(['contact_name' => 'Anonymised Customer']);

        $contact->forceDelete();

        $this->line("Anonymized contact: {$contact->id}");
    }
}
