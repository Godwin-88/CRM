<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateContactExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(
        protected array $filters,
        protected array $fields,
        protected string $userId,
    ) {}

    public function handle(): void
    {
        $query = Contact::query();

        // Apply filters
        foreach ($this->filters as $field => $value) {
            if (!empty($value)) {
                if ($field === 'search') {
                    $query->where(function ($q) use ($value) {
                        $q->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%");
                    });
                } elseif (in_array($field, ['type', 'status', 'source', 'loyalty_tier'])) {
                    $query->where($field, $value);
                } elseif ($field === 'owner_id') {
                    $query->where('owner_id', $value);
                } elseif ($field === 'created_from') {
                    $query->whereDate('created_at', '>=', $value);
                } elseif ($field === 'created_to') {
                    $query->whereDate('created_at', '<=', $value);
                }
            }
        }

        $filename = 'exports/contacts_' . Str::ulid() . '.csv';
        $filePath = Storage::path($filename);

        $file = fopen($filePath, 'w');
        fputcsv($file, $this->fields);

        $query->chunk(500, function ($contacts) use ($file) {
            foreach ($contacts as $contact) {
                $row = [];
                foreach ($this->fields as $field) {
                    $row[] = $contact->{$field} ?? '';
                }
                fputcsv($file, $row);
            }
        });

        fclose($file);

        // Log export to audit
        activity()
            ->causedBy(\App\Models\User::find($this->userId))
            ->withProperties([
                'filename' => $filename,
                'record_count' => $query->count(),
                'fields' => $this->fields,
            ])
            ->event('export')
            ->log('Contact export generated');

        // In a real app, send in-app notification with download link
        Log::info("Export ready for user {$this->userId}: {$filename}");
    }
}