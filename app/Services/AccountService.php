<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CustomFieldValue;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function createAccount(array $data, array $customFields = []): Account
    {
        return DB::transaction(function () use ($data, $customFields) {
            $account = Account::create($data);

            // Save custom field values
            $this->saveCustomFieldValues($account, $customFields);

            activity()
                ->performedOn($account)
                ->causedBy(auth()->user())
                ->withProperties(['new_values' => $data])
                ->event('created')
                ->log('Account created');

            return $account;
        });
    }

    public function updateAccount(Account $account, array $data, array $customFields = []): Account
    {
        return DB::transaction(function () use ($account, $data, $customFields) {
            $oldValues = $account->fresh()->toArray();
            $changedFields = [];

            foreach ($data as $field => $newValue) {
                if (array_key_exists($field, $oldValues) && $oldValues[$field] != $newValue) {
                    $changedFields[$field] = [
                        'old' => $oldValues[$field],
                        'new' => $newValue,
                    ];
                }
            }

            $account->update($data);

            // Save custom field values
            $this->saveCustomFieldValues($account, $customFields);

            if (!empty($changedFields)) {
                activity()
                    ->performedOn($account)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old_values' => array_map(fn($v) => $v['old'], $changedFields),
                        'new_values' => array_map(fn($v) => $v['new'], $changedFields),
                        'changed_fields' => array_keys($changedFields),
                    ])
                    ->event('updated')
                    ->log('Account updated');
            }

            return $account->fresh();
        });
    }

    public function deleteAccount(Account $account): bool|null
    {
        return DB::transaction(function () use ($account) {
            activity()
                ->performedOn($account)
                ->causedBy(auth()->user())
                ->withProperties(['deleted_at' => now()])
                ->event('deleted')
                ->log('Account soft-deleted');

            return $account->delete();
        });
    }

    /**
     * Save custom field values for an account.
     */
    public function saveCustomFieldValues(Account $account, array $customFields): void
    {
        foreach ($customFields as $fieldKey => $value) {
            CustomFieldValue::updateOrCreate(
                [
                    'customizable_type' => get_class($account),
                    'customizable_id' => $account->id,
                    'field_key' => $fieldKey,
                ],
                ['value' => $value]
            );
        }
    }
}