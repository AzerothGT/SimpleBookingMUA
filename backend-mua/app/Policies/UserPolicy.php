<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->role === 'admin';
    }

    public function view(User $actor, User $user): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function assignRole(User $actor, string $role): bool
    {
        return $actor->role === 'admin';
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->role === 'admin';
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->id !== $user->id && $this->update($actor, $user);
    }
}
