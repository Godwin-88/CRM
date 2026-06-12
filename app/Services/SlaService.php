<?php

namespace App\Services;

use App\Models\SlaDefinition;
use App\Models\Ticket;
use App\Models\BusinessHours;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SlaService
{
    public function assignSlaToTicket(Ticket $ticket): ?SlaDefinition
    {
        $contact = $ticket->contact;
        if (!$contact) {
            return null;
        }

        $tier = $contact->loyalty_tier;
        $accountType = $contact->type;

        $sla = SlaDefinition::where(function ($query) use ($tier, $accountType) {
            $query->where('loyalty_tier_id', $tier)
                  ->orWhere('account_type', $accountType)
                  ->orWhere('is_default', true);
        })
        ->orderByRaw("CASE WHEN loyalty_tier_id = ? THEN 0 WHEN account_type = ? THEN 1 ELSE 2 END", [$tier, $accountType])
        ->first();

        if (!$sla) {
            return null;
        }

        $businessHours = $sla->businessHours()->first();
        if (!$businessHours) {
            $businessHours = BusinessHours::where('is_global', true)->first();
        }

        if (!$businessHours) {
            return $sla;
        }

        $now = Carbon::now();
        $firstResponseDeadline = $this->calculateDeadline($now, $sla->first_response_time_business_hours, $businessHours);
        $resolutionDeadline = $this->calculateDeadline($now, $sla->resolution_time_business_hours, $businessHours);

        DB::table('sla_instances')->insert([
            'id' => uniqid(),
            'ticket_id' => $ticket->id,
            'sla_definition_id' => $sla->id,
            'assigned_at' => $now,
            'first_response_deadline' => $firstResponseDeadline,
            'resolution_deadline' => $resolutionDeadline,
        ]);

        return $sla;
    }

    public function calculateDeadline(Carbon $start, ?int $businessHours, BusinessHours $bizHours): ?Carbon
    {
        if (!$businessHours) {
            return $start->addHours(24);
        }

        $deadline = $start->copy();
        $hoursRemaining = $businessHours;
        $daysOfWeek = $bizHours->days_of_week ?? [1, 2, 3, 4, 5];
        $startTime = Carbon::createFromFormat('H:i', $bizHours->start_time);
        $endTime = Carbon::createFromFormat('H:i', $bizHours->end_time);

        while ($hoursRemaining > 0) {
            if (in_array($deadline->dayOfWeek, $daysOfWeek)) {
                $currentTime = $deadline->copy()->setTimeFromTimeString($deadline->format('H:i'));

                if ($currentTime->lt($startTime)) {
                    $deadline->setTime($startTime->hour, $startTime->minute);
                } elseif ($currentTime->gte($endTime)) {
                    $deadline->addDay()->setTime($startTime->hour, $startTime->minute);
                    continue;
                }

                $availableHours = $endTime->diffInHours($currentTime);
                $hoursToAdd = min($hoursRemaining, $availableHours);

                $deadline->addHours($hoursToAdd);
                $hoursRemaining -= $hoursToAdd;

                if ($hoursRemaining > 0) {
                    $deadline->addDay()->setTime($startTime->hour, $startTime->minute);
                }
            } else {
                $deadline->addDay();
            }
        }

        return $deadline;
    }

    public function checkBreaches(): void
    {
        $instances = DB::table('sla_instances')
            ->where('first_response_breached', false)
            ->where('first_response_deadline', '<', now())
            ->get();

        foreach ($instances as $instance) {
            DB::table('sla_instances')
                ->where('id', $instance->id)
                ->update(['first_response_breached' => true]);

            $ticket = Ticket::find($instance->ticket_id);
            if ($ticket) {
                $this->notifyBreach($ticket, 'first_response');
            }
        }
    }

    private function notifyBreach(Ticket $ticket, string $type): void
    {
        $agent = $ticket->assignee;
        if ($agent) {
            \App\Models\Notification::create([
                'user_id' => $agent->id,
                'type' => 'sla_breach',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_subject' => $ticket->subject,
                    'breach_type' => $type,
                ],
            ]);
        }
    }
}
