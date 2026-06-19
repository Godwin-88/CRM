<?php

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Services\ExploratoryAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class DeliverScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [0, 1800];

    public function __construct(public ScheduledReport $scheduledReport) {}

    public function handle(ExploratoryAnalyticsService $explorer): void
    {
        try {
            $report = $this->scheduledReport->report;
            $data = $explorer->runReport($report)['rows'];

            $csv = Writer::createFromString('');
            $headers = isset($data[0]) && is_array($data[0]) ? array_keys($data[0]) : ['row'];

            $csv->insertOne($headers);
            foreach ($data as $row) {
                $csv->insertOne(array_values(is_array($row) ? $row : ['row' => $row]));
            }

            $filename = "reports/{$report->id}_{$this->scheduledReport->id}.csv";
            Storage::disk('local')->put($filename, $csv->getContent());

            foreach ($this->scheduledReport->recipients as $email) {
                Mail::raw("Report: {$report->name}\nRows: ".count($data)."\n", function ($message) use ($email, $filename, $report) {
                    $message->to($email)
                        ->subject("Report: {$report->name}")
                        ->attach(Storage::disk('local')->path($filename));
                });
            }

            $this->scheduledReport->update([
                'last_run_at' => now(),
                'next_run_at' => $this->nextRunAt(),
            ]);

            $this->scheduledReport->deliveryLogs()->create([
                'sent_at' => now(),
                'recipients' => $this->scheduledReport->recipients,
                'row_count' => count($data),
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            $this->scheduledReport->deliveryLogs()->create([
                'sent_at' => now(),
                'recipients' => $this->scheduledReport->recipients,
                'row_count' => 0,
                'success' => false,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function nextRunAt(): \DateTime
    {
        return match ($this->scheduledReport->frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->next($this->scheduledReport->day_of_week ?? 1),
            'monthly' => now()->addMonth()->setDay($this->scheduledReport->day_of_month ?? 1),
            default => now()->addDay(),
        };
    }
}
