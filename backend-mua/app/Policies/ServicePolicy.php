<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin'], true);
    }

    public function update(User $user, Service $service): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->create($user);
    }
}
