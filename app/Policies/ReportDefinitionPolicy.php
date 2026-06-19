<?php

namespace App\Policies;

use App\Models\ReportDefinition;
use App\Models\User;

class ReportDefinitionPolicy
{
    public function view(User $user, ReportDefinition $report): bool
    {
        return $report->visibility === 'shared'
            || $report->owner_id === $user->id
            || $user->hasRole(['manager', 'admin']);
    }

    public function update(User $user, ReportDefinition $report): bool
    {
        return $report->owner_id === $user->id || $user->hasRole('admin');
    }

    public function delete(User $user, ReportDefinition $report): bool
    {
        return $report->owner_id === $user->id || $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('agent');
    }
}
