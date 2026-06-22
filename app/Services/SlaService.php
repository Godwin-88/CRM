<?php

namespace App\Services;

use App\Models\BusinessHours;
use App\Models\CaseRecord;
use App\Models\Holiday;
use App\Models\ServiceRequest;
use App\Models\SlaDefinition;
use App\Models\SlaInstance;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SlaBreachWarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SlaService
{
    public function assignSlaToTicket(Ticket $ticket): ?SlaDefinition
    {
        $contact = $ticket->contact;
        if (! $contact) {
            return null;
        }

        $tier = $contact->loyalty_tier;
        $accountType = $contact->type;

        // First try: match by loyalty tier
        $sla = SlaDefinition::where('loyalty_tier_id', $tier)->first();

        // Second try: match by account type
        if (! $sla && $accountType) {
            $sla = SlaDefinition::where('account_type', $accountType)->first();
        }

        // Third try: default SLA
        if (! $sla) {
            $sla = SlaDefinition::where('is_default', true)->first();
        }

        if (! $sla) {
            return null;
        }

        $businessHours = $sla->businessHours()->first();
        if (! $businessHours) {
            $businessHours = BusinessHours::where('is_global', true)->first();
        }

        if (! $businessHours) {
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

    public function assignSlaToServiceRequest(ServiceRequest $serviceRequest): ?SlaDefinition
    {
        $sla = $this->resolveSlaForServiceRequest($serviceRequest);

        if (! $sla) {
            return null;
        }

        $businessHours = $this->businessHoursForSla($sla);
        if (! $businessHours) {
            return $sla;
        }

        $now = Carbon::now();
        $deadlines = [
            'acknowledgement_deadline' => $this->calculateDeadline($now, $sla->acknowledgement_time_business_hours, $businessHours),
            'review_deadline' => $this->calculateDeadline($now, $sla->review_time_business_hours, $businessHours),
            'next_action_deadline' => $this->calculateDeadline($now, $sla->next_action_time_business_hours, $businessHours),
            'completion_deadline' => $this->calculateDeadline($now, $sla->completion_time_business_hours, $businessHours),
        ];

        SlaInstance::create([
            'id' => (string) Str::ulid(),
            'target_type' => 'service_request',
            'target_id' => $serviceRequest->id,
            'entity_type' => 'service_request',
            'sla_definition_id' => $sla->id,
            'assigned_at' => $now,
            ...$deadlines,
        ]);

        $serviceRequest->update(['sla_instance_id' => SlaInstance::where('target_type', 'service_request')->where('target_id', $serviceRequest->id)->latest('created_at')->value('id')]);

        return $sla;
    }

    public function assignSlaToCase(CaseRecord $case): ?SlaDefinition
    {
        $sla = $this->resolveSlaForCase($case);

        if (! $sla) {
            return null;
        }

        $businessHours = $this->businessHoursForSla($sla);
        if (! $businessHours) {
            return $sla;
        }

        $now = Carbon::now();
        $deadlines = [
            'triage_deadline' => $this->calculateDeadline($now, $sla->triage_time_business_hours, $businessHours),
            'investigation_update_deadline' => $this->calculateDeadline($now, $sla->investigation_update_time_business_hours, $businessHours),
            'resolution_proposal_deadline' => $this->calculateDeadline($now, $sla->resolution_proposal_time_business_hours, $businessHours),
            'closure_signoff_deadline' => $this->calculateDeadline($now, $sla->closure_signoff_time_business_hours, $businessHours),
        ];

        SlaInstance::create([
            'id' => (string) Str::ulid(),
            'target_type' => 'case',
            'target_id' => $case->id,
            'entity_type' => 'case',
            'sla_definition_id' => $sla->id,
            'assigned_at' => $now,
            ...$deadlines,
        ]);

        $case->update(['sla_instance_id' => SlaInstance::where('target_type', 'case')->where('target_id', $case->id)->latest('created_at')->value('id')]);

        return $sla;
    }

    protected function resolveSlaForServiceRequest(ServiceRequest $serviceRequest): ?SlaDefinition
    {
        $catalogItem = $serviceRequest->catalogItem;

        if ($catalogItem?->sla_policy_id) {
            return SlaDefinition::find($catalogItem->sla_policy_id);
        }

        $contact = $serviceRequest->contact;
        $tier = $contact?->loyalty_tier;
        $accountType = $contact?->type;

        if ($tier) {
            $sla = SlaDefinition::where('loyalty_tier_id', $tier)->first();
            if ($sla) {
                return $sla;
            }
        }

        if ($accountType) {
            $sla = SlaDefinition::where('account_type', $accountType)->first();
            if ($sla) {
                return $sla;
            }
        }

        return SlaDefinition::where('is_default', true)->first();
    }

    protected function resolveSlaForCase(CaseRecord $case): ?SlaDefinition
    {
        $contact = $case->primaryContact;
        $tier = $contact?->loyalty_tier;
        $accountType = $contact?->type;

        if ($tier) {
            $sla = SlaDefinition::where('loyalty_tier_id', $tier)->first();
            if ($sla) {
                return $sla;
            }
        }

        if ($accountType) {
            $sla = SlaDefinition::where('account_type', $accountType)->first();
            if ($sla) {
                return $sla;
            }
        }

        return SlaDefinition::where('is_default', true)->first();
    }

    protected function businessHoursForSla(SlaDefinition $sla): ?BusinessHours
    {
        $businessHours = $sla->businessHours()->first();

        if (! $businessHours) {
            $businessHours = BusinessHours::where('is_global', true)->first();
        }

        return $businessHours;
    }

    public function calculateDeadline(Carbon $start, ?int $businessHours, BusinessHours $bizHours): ?Carbon
    {
        if (! $businessHours) {
            return $start->addHours(24);
        }

        $deadline = $start->copy();
        $hoursRemaining = $businessHours;
        $daysOfWeek = $bizHours->days_of_week ?? [1, 2, 3, 4, 5];
        $startTime = Carbon::createFromFormat('H:i', $bizHours->start_time);
        $endTime = Carbon::createFromFormat('H:i', $bizHours->end_time);

        while ($hoursRemaining > 0) {
            if ($this->isHoliday($deadline)) {
                $deadline->addDay();

                continue;
            }

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

    public function pauseTimer(Ticket $ticket): void
    {
        $instance = $ticket->slaInstance;
        if (! $instance || $instance->first_response_met_at || $instance->resolution_met_at) {
            return;
        }

        if ($instance->paused_at) {
            return;
        }

        $instance->update(['paused_at' => now()]);
    }

    public function resumeTimer(Ticket $ticket): void
    {
        $instance = $ticket->slaInstance;
        if (! $instance || ! $instance->paused_at || $instance->first_response_met_at || $instance->resolution_met_at) {
            return;
        }

        $pausedDuration = now()->diffInSeconds($instance->paused_at);

        $instance->update([
            'paused_at' => null,
            'resumed_at' => now(),
            'first_response_deadline' => DB::raw("first_response_deadline + INTERVAL '{$pausedDuration} seconds'"),
            'resolution_deadline' => DB::raw("resolution_deadline + INTERVAL '{$pausedDuration} seconds'"),
        ]);
    }

    public function checkFirstResponseMet(Ticket $ticket): void
    {
        $instance = $ticket->slaInstance;
        if (! $instance || $instance->first_response_met_at) {
            return;
        }

        $hasOutboundReply = $ticket->interactions()
            ->where('direction', 'outbound')
            ->exists();

        if ($hasOutboundReply) {
            $instance->update(['first_response_met_at' => now()]);
        }
    }

    public function checkResolutionMet(Ticket $ticket): void
    {
        $instance = $ticket->slaInstance;
        if (! $instance || $instance->resolution_met_at) {
            return;
        }

        if ($ticket->status === Ticket::STATUS_RESOLVED) {
            $instance->update(['resolution_met_at' => now()]);
        }
    }

    public function checkBreaches(): void
    {
        $this->checkFirstResponseBreaches();
        $this->checkResolutionBreaches();
        $this->checkBreachWarnings();
    }

    protected function checkFirstResponseBreaches(): void
    {
        $instances = DB::table('sla_instances')
            ->where('first_response_breached', false)
            ->whereNotNull('first_response_deadline')
            ->where('first_response_deadline', '<', now())
            ->get();

        foreach ($instances as $instance) {
            DB::table('sla_instances')
                ->where('id', $instance->id)
                ->update(['first_response_breached' => true]);

            $ticket = Ticket::find($instance->ticket_id);
            if ($ticket) {
                $ticket->update(['sla_breached_at' => now()]);
                $this->notifyBreach($ticket, 'first_response');
            }
        }
    }

    protected function checkResolutionBreaches(): void
    {
        $instances = DB::table('sla_instances')
            ->where('resolution_breached', false)
            ->whereNotNull('resolution_deadline')
            ->where('resolution_deadline', '<', now())
            ->get();

        foreach ($instances as $instance) {
            $instanceRecord = SlaInstance::find($instance->id);
            if (! $instanceRecord || $instanceRecord->resolution_met_at) {
                continue;
            }

            DB::table('sla_instances')
                ->where('id', $instance->id)
                ->update(['resolution_breached' => true]);

            $ticket = Ticket::find($instance->ticket_id);
            if ($ticket) {
                $ticket->update(['sla_breached_at' => now()]);
                $this->notifyBreach($ticket, 'resolution');
            }
        }
    }

    protected function checkBreachWarnings(): void
    {
        $instances = DB::table('sla_instances')
            ->where('first_response_breached', false)
            ->where('resolution_breached', false)
            ->get();

        foreach ($instances as $instance) {
            $sla = SlaDefinition::find($instance->sla_definition_id);
            if (! $sla) {
                continue;
            }

            $warningThreshold = 0.8;
            $now = now();
            $ticket = Ticket::find($instance->ticket_id);

            if ($instance->first_response_deadline && ! $instance->first_response_met_at) {
                $elapsed = $instance->assigned_at->diffInSeconds($now);
                $total = $instance->assigned_at->diffInSeconds($instance->first_response_deadline);
                if ($total > 0 && ($elapsed / $total) >= $warningThreshold) {
                    $this->sendWarning($ticket, $instance->ticket_id, 'first_response');
                }
            }

            if ($instance->resolution_deadline && ! $instance->resolution_met_at) {
                $elapsed = $instance->assigned_at->diffInSeconds($now);
                $total = $instance->assigned_at->diffInSeconds($instance->resolution_deadline);
                if ($total > 0 && ($elapsed / $total) >= $warningThreshold) {
                    $this->sendWarning($ticket, $instance->ticket_id, 'resolution');
                }
            }
        }
    }

    public function getBreachedTickets()
    {
        return Ticket::whereNotNull('sla_breached_at')
            ->whereHas('slaInstance')
            ->with(['slaInstance.slaDefinition', 'assignee', 'contact'])
            ->orderBy('sla_breached_at', 'desc');
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

    private function sendWarning(?Ticket $ticket, string $ticketId, string $type): void
    {
        $ticket = $ticket ?? Ticket::find($ticketId);
        if (! $ticket) {
            return;
        }

        $agent = $ticket->assignee;
        $manager = $this->getManagerForTicket($ticket);

        if ($agent) {
            Notification::send($agent, new SlaBreachWarning($ticket, $type));
        }

        if ($manager) {
            Notification::send($manager, new SlaBreachWarning($ticket, $type));
        }
    }

    private function getManagerForTicket(Ticket $ticket): ?User
    {
        $assignment = TeamMember::where('user_id', $ticket->assigned_to)
            ->first();

        if (! $assignment) {
            return null;
        }

        $managerAssignment = TeamMember::where('team_id', $assignment->team_id)
            ->where('role', 'manager')
            ->first();

        return $managerAssignment?->user;
    }

    private function isHoliday(Carbon $date): bool
    {
        return Holiday::where('date', $date->toDateString())->exists();
    }
}
