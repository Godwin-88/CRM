<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

class DuplicateDetectionService
{
    /**
     * Find potential duplicate contacts.
     *
     * @param array $contactData
     * @return Collection|Contact[]
     */
    public function findDuplicates(array $contactData): Collection
    {
        $query = Contact::query();

        if (!empty($contactData['email'])) {
            // Check exact email match (excluding soft-deleted)
            $query->orWhere(function ($q) use ($contactData) {
                $q->where('email', $contactData['email']);
            });
        }

        if (!empty($contactData['first_name']) && !empty($contactData['last_name']) && !empty($contactData['phone'])) {
            // Check first_name + last_name + phone combination
            $query->orWhere(function ($q) use ($contactData) {
                $q->where('first_name', $contactData['first_name'])
                  ->where('last_name', $contactData['last_name'])
                  ->where('phone', $contactData['phone']);
            });
        }

        // Exclude the current contact if an ID is provided (for update scenarios)
        if (!empty($contactData['id'])) {
            $query->where('id', '!=', $contactData['id']);
        }

        return $query->get();
    }

    /**
     * Find exact duplicate by email.
     */
    public function findByEmail(string $email, ?string $excludeId = null): ?Contact
    {
        $query = Contact::where('email', $email);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->first();
    }
}