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

    public function __construct(public ScheduledReport $scheduledReport) {}

    public function handle(ExploratoryAnalyticsService $explorer): void
    {
        $report = $this->scheduledReport->report;
        $data = $explorer->runReport($report);

        $csv = Writer::createFromString('');
        $headers = ['id'];
        if (isset($data[0]) && is_array($data[0])) {
            $headers = array_keys($data[0]);
        }
        $csv->insertOne($headers);
        foreach ($data as $row) {
            $csv->insertOne(array_values($row));
        }

        $filename = "reports/{$report->id}_{$this->scheduledReport->id}.csv";
        Storage::put($filename, $csv->getContent());

        foreach ($this->scheduledReport->recipients as $email) {
            Mail::raw('', function ($message) use ($email, $filename, $report) {
                $message->to($email)
                    ->subject("Report: {$report->name}")
                    ->attach(Storage::path($filename));
            });
        }

        $this->scheduledReport->update([
            'last_run_at' => now(),
        ]);

        $this->scheduledReport->deliveryLogs()->create([
            'sent_at' => now(),
            'recipients' => $this->scheduledReport->recipients,
            'row_count' => count($data),
        ]);
    }
}