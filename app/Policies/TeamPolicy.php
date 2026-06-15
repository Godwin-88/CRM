<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, Team $team): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $this->isTeamLead($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasRole('admin');
    }

    public function addMember(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $this->isTeamLead($user, $team);
    }

    public function removeMember(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $this->isTeamLead($user, $team);
    }

    protected function isTeamLead(User $user, Team $team): bool
    {
        return $team->team_lead_id === $user->id;
    }
}