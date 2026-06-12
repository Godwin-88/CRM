<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\DuplicateDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessContactImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    /**
     * @param string $filePath Path to uploaded file in storage
     * @param array $fieldMapping ['csv_column' => 'contact_field']
     * @param string $userId Who initiated the import
     */
    public function __construct(
        protected string $filePath,
        protected array $fieldMapping,
        protected string $userId,
    ) {}

    public function handle(DuplicateDetectionService $duplicateService): void
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $contents = Storage::get($this->filePath);
            $lines = explode("\n", trim($contents));
            $headers = str_getcsv(array_shift($lines));

            foreach ($lines as $lineIndex => $line) {
                if (empty(trim($line))) continue;

                $row = str_getcsv($line);
                $rowData = [];

                // Map CSV columns to contact fields
                foreach ($headers as $colIndex => $header) {
                    if (!isset($this->fieldMapping[$header])) continue;
                    $field = $this->fieldMapping[$header];
                    $rowData[$field] = $row[$colIndex] ?? '';
                }

                // Validate required fields
                if (empty($rowData['first_name']) || empty($rowData['last_name']) || empty($rowData['email']) || empty($rowData['type'])) {
                    $results['skipped']++;
                    $results['errors'][] = "Row " . ($lineIndex + 2) . ": Missing required fields (first_name, last_name, email, type)";
                    continue;
                }

                // Check for duplicate email
                $existing = $duplicateService->findByEmail($rowData['email']);
                if ($existing) {
                    $results['skipped']++;
                    $results['errors'][] = "Row " . ($lineIndex + 2) . ": Duplicate email '{$rowData['email']}' (existing contact: {$existing->first_name} {$existing->last_name})";
                    continue;
                }

                try {
                    Contact::create($rowData);
                    $results['created']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($lineIndex + 2) . ": " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            Log::error('Contact import failed: ' . $e->getMessage());
            $results['failed']++;
            $results['errors'][] = 'File processing error: ' . $e->getMessage();
        } finally {
            // Clean up uploaded file
            Storage::delete($this->filePath);
        }

        // Log import result to audit
        activity()
            ->causedBy(\App\Models\User::find($this->userId))
            ->withProperties([
                'import_results' => $results,
                'file' => $this->filePath,
            ])
            ->event('import')
            ->log('Contact import completed: ' . json_encode($results));

        // In a real app, send in-app notification to the user
        Log::info('Import completed for user ' . $this->userId, $results);
    }
}