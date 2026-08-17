<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->viewAny($user);
    }
}
