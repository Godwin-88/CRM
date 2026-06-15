<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\DemoTrial;
use App\Models\ContractMilestone;
use App\Models\OnboardingActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    public function getEvents(string $userId, ?string $teamId, string $view, Carbon $start, Carbon $end): Collection
    {
        $events = collect();

        $events = $events->merge($this->getActivityEvents($userId, $teamId, $start, $end));
        $events = $events->merge($this->getDemoTrialEvents($userId, $teamId, $start, $end));
        $events = $events->merge($this->getContractMilestoneEvents($userId, $teamId, $start, $end));
        $events = $events->merge($this->getOnboardingEvents($userId, $teamId, $start, $end));

        return $events->sortBy('date')->values();
    }

    protected function getActivityEvents($userId, $teamId, $start, $end): Collection
    {
        $query = Activity::whereBetween('due_at', [$start, $end]);

        if ($teamId) {
            $query->whereHas('assignee.teamMembers', fn ($q) => $q->where('team_id', $teamId));
        } else {
            $query->where('assigned_to', $userId);
        }

        return $query->get()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => 'activity',
                'title' => $activity->subject,
                'date' => $activity->due_at,
                'color' => 'blue',
                'url' => '/activities/'.$activity->id,
                'editable' => true,
            ];
        });
    }

    protected function getDemoTrialEvents($userId, $teamId, $start, $end): Collection
    {
        $query = DemoTrial::whereBetween('scheduled_at', [$start, $end]);

        if ($teamId) {
            $query->whereHas('deal.owner.teamMembers', fn ($q) => $q->where('team_id', $teamId));
        } else {
            $query->whereHas('deal', fn ($q) => $q->where('owner_id', $userId));
        }

        return $query->get()->map(function ($demo) {
            return [
                'id' => $demo->id,
                'type' => 'demo',
                'title' => $demo->deal->title ?? 'Demo',
                'date' => $demo->scheduled_at,
                'color' => 'indigo',
                'url' => '/deals/'.$demo->deal_id,
                'editable' => true,
            ];
        });
    }

    protected function getContractMilestoneEvents($userId, $teamId, $start, $end): Collection
    {
        $query = ContractMilestone::whereBetween('date', [$start, $end]);

        return $query->get()->map(function ($milestone) {
            return [
                'id' => $milestone->id,
                'type' => 'contract_milestone',
                'title' => $milestone->title ?? 'Contract Milestone',
                'date' => $milestone->date,
                'color' => $this->getContractMilestoneColor($milestone),
                'url' => '/contracts/'.$milestone->contract_id,
                'editable' => false,
            ];
        });
    }

    protected function getOnboardingEvents($userId, $teamId, $start, $end): Collection
    {
        $query = OnboardingActivity::whereBetween('due_date', [$start, $end]);

        return $query->get()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => 'onboarding',
                'title' => $activity->name ?? 'Onboarding Step',
                'date' => $activity->due_date,
                'color' => 'amber',
                'url' => '/contacts/'.$activity->record->contact_id,
                'editable' => true,
            ];
        });
    }

    protected function getContractMilestoneColor($milestone): string
    {
        $contract = $milestone->contract;

        if ($contract->status === 'draft') {
            return 'gray';
        }
        if ($contract->status === 'sent') {
            return 'indigo';
        }
        if ($contract->status === 'signed' || $contract->status === 'active') {
            return 'teal';
        }
        if ($contract->status === 'expiring') {
            return 'amber';
        }
        if ($contract->status === 'expired') {
            return 'red';
        }

        return 'gray';
    }
}