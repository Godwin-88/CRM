<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\CustomFieldValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactService
{
    public function createContact(array $data, array $customFields = []): Contact
    {
        return DB::transaction(function () use ($data, $customFields) {
            // Ensure unique email excluding soft-deleted records
            if (isset($data['email'])) {
                $existing = Contact::where('email', $data['email'])->first();
                if ($existing) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        response()->json([
                            'message' => 'A contact with this email already exists.',
                            'errors' => ['email' => ['Email is already in use by contact: ' . $existing->first_name . ' ' . $existing->last_name]],
                        ], 422)
                    );
                }
            }

            $contact = Contact::create($data);

            // Save custom field values
            $this->saveCustomFieldValues($contact, $customFields);

            // Log audit
            activity()
                ->performedOn($contact)
                ->causedBy(auth()->user())
                ->withProperties(['new_values' => $data])
                ->event('created')
                ->log('Contact created');

            return $contact;
        });
    }

    public function updateContact(Contact $contact, array $data, array $customFields = []): Contact
    {
        return DB::transaction(function () use ($contact, $data, $customFields) {
            $oldValues = $contact->fresh()->toArray();
            $changedFields = [];

            // Check for each changed field and collect old/new
            foreach ($data as $field => $newValue) {
                if (isset($oldValues[$field]) && $oldValues[$field] != $newValue) {
                    $changedFields[$field] = [
                        'old' => $oldValues[$field],
                        'new' => $newValue,
                    ];
                }
            }

            $contact->update($data);

            // Save custom field values
            $this->saveCustomFieldValues($contact, $customFields);

            // Log audit with old and new values for changed fields
            if (!empty($changedFields)) {
                activity()
                    ->performedOn($contact)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old_values' => array_map(fn($v) => $v['old'], $changedFields),
                        'new_values' => array_map(fn($v) => $v['new'], $changedFields),
                        'changed_fields' => array_keys($changedFields),
                    ])
                    ->event('updated')
                    ->log('Contact updated');
            }

            return $contact->fresh();
        });
    }

    public function deleteContact(Contact $contact): bool|null
    {
        return DB::transaction(function () use ($contact) {
            activity()
                ->performedOn($contact)
                ->causedBy(auth()->user())
                ->withProperties(['deleted_at' => now()])
                ->event('deleted')
                ->log('Contact soft-deleted');

            return $contact->delete();
        });
    }

    /**
     * Merge two contacts.
     */
    public function mergeContacts(string $survivingId, string $discardedId, array $fieldSelections): Contact
    {
        return DB::transaction(function () use ($survivingId, $discardedId, $fieldSelections) {
            $surviving = Contact::findOrFail($survivingId);
            $discarded = Contact::findOrFail($discardedId);

            // Update surviving contact with selected field values
            $updateData = [];
            foreach ($fieldSelections as $field => $value) {
                if (in_array($field, $surviving->getFillable())) {
                    $updateData[$field] = $value;
                }
            }
            $surviving->update($updateData);

            // Re-link all related data from discarded to surviving
            // Interactions
            $discarded->interactions()->update(['contact_id' => $survivingId]);
            // Activities
            $discarded->activities()->update(['contact_id' => $survivingId]);
            // Deals
            $discarded->deals()->update(['contact_id' => $survivingId]);
            // Tickets
            $discarded->tickets()->update(['contact_id' => $survivingId]);
            // Contracts
            $discarded->contracts()->update(['contact_id' => $survivingId]);
            // Accounts (pivot)
            $surviving->accounts()->syncWithoutDetaching(
                $discarded->accounts()->pluck('accounts.id')->toArray()
            );

            // Log merge event
            activity()
                ->performedOn($surviving)
                ->causedBy(auth()->user())
                ->withProperties([
                    'surviving_id' => $survivingId,
                    'discarded_id' => $discardedId,
                    'merged_fields' => $fieldSelections,
                ])
                ->event('merged')
                ->log('Contacts merged');

            // Soft delete discarded
            $discarded->delete();

            return $surviving->fresh();
        });
    }

    /**
     * Save custom field values for a contact.
     */
    public function saveCustomFieldValues(Contact $contact, array $customFields): void
    {
        foreach ($customFields as $fieldKey => $value) {
            CustomFieldValue::updateOrCreate(
                [
                    'customizable_type' => get_class($contact),
                    'customizable_id' => $contact->id,
                    'field_key' => $fieldKey,
                ],
                ['value' => $value]
            );
        }
    }
}