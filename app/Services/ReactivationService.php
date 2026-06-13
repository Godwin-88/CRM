<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\DripEnrolment;
use App\Models\DripSequence;
use App\Models\Notification;
use App\Models\ReactivationConfig;
use App\Models\ReactivationContact;

class ReactivationService
{
    public function evaluateTriggers(): void
    {
        $configs = ReactivationConfig::where('is_active', true)->get();

        foreach ($configs as $config) {
            $this->processConfig($config);
        }
    }

    private function processConfig(ReactivationConfig $config): void
    {
        $thresholdDays = $config->inactivity_days_threshold;
        $cutoffDate = now()->subDays($thresholdDays);

        $contacts = Contact::where('type', $config->contact_type)
            ->where(function ($query) use ($cutoffDate) {
                $query->where('updated_at', '<', $cutoffDate)
                    ->orWhereNull('updated_at');
            })
            ->whereDoesntHave('reactivationContacts', function ($query) {
                $query->whereIn('status', ['enrolled', 're_engaged']);
            })
            ->get();

        foreach ($contacts as $contact) {
            $this->enrollContact($contact, $config);
        }
    }

    public function enrollContact(Contact $contact, ReactivationConfig $config): void
    {
        if (! $config->drip_sequence_id) {
            return;
        }

        $sequence = DripSequence::find($config->drip_sequence_id);
        if (! $sequence || ! $sequence->isActive()) {
            return;
        }

        $existingEnrolment = DripEnrolment::where('contact_id', $contact->id)
            ->where('status', 'active')
            ->first();

        if ($existingEnrolment) {
            return;
        }

        $enrolment = DripEnrolment::create([
            'drip_sequence_id' => $config->drip_sequence_id,
            'contact_id' => $contact->id,
            'status' => 'active',
            'enroled_at' => now(),
        ]);

        ReactivationContact::create([
            'config_id' => $config->id,
            'contact_id' => $contact->id,
            'drip_enrolment_id' => $enrolment->id,
            'status' => 'enrolled',
        ]);
    }

    public function handleReEngagement(int $contactId): void
    {
        $reactivation = ReactivationContact::where('contact_id', $contactId)
            ->where('status', 'enrolled')
            ->first();

        if (! $reactivation) {
            return;
        }

        $reactivation->update([
            'status' => 're_engaged',
            're_engaged_at' => now(),
        ]);

        if ($reactivation->drip_enrolment_id) {
            $enrolment = DripEnrolment::find($reactivation->drip_enrolment_id);
            if ($enrolment) {
                $enrolment->update(['status' => 'completed']);
            }
        }

        $contact = Contact::find($contactId);
        if ($contact && $contact->owner_id) {
            Notification::create([
                'user_id' => $contact->owner_id,
                'type' => 'contact_reengaged',
                'data' => [
                    'contact_id' => $contactId,
                    'contact_name' => $contact->first_name.' '.$contact->last_name,
                ],
            ]);
        }
    }

    public function completeFullSequence(int $contactId, ?string $dormantTag = null): void
    {
        $reactivation = ReactivationContact::where('contact_id', $contactId)
            ->where('status', 'enrolled')
            ->first();

        if ($reactivation) {
            $reactivation->update(['status' => 'dormant']);

            if ($dormantTag) {
                $contact = Contact::find($contactId);
                if ($contact) {
                    $tags = $contact->tags ?? [];
                    if (! in_array($dormantTag, $tags)) {
                        $tags[] = $dormantTag;
                        $contact->update(['tags' => $tags]);
                    }
                }
            }
        }
    }

    public function getAnalytics(): array
    {
        $contacts = ReactivationContact::all();

        return [
            'total_enrolled' => $contacts->where('status', 'enrolled')->count(),
            'total_re_engaged' => $contacts->where('status', 're_engaged')->count(),
            'total_dormant' => $contacts->where('status', 'dormant')->count(),
            're_engagement_rate' => $contacts->count() > 0
                ? round(($contacts->where('status', 're_engaged')->count() / $contacts->count()) * 100, 2)
                : 0,
        ];
    }
}
