<?php

namespace App\Actions\Users;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManageUser
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $user = User::create($data);
            $this->recordActivity->handle($actor, $user, 'user.created');

            return $user;
        });
    }

    public function update(User $user, User $actor, array $data): User
    {
        return DB::transaction(function () use ($user, $actor, $data): User {
            $before = $user->only(array_keys($data));
            $user->update($data);
            $this->recordActivity->handle($actor, $user, 'user.updated', meta: [
                'before' => $before,
                'after' => $user->only(array_keys($data)),
            ]);

            return $user->refresh();
        });
    }

    public function deactivate(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $before = ['is_active' => $user->is_active];
            $user->update(['is_active' => false]);
            $this->recordActivity->handle($actor, $user, 'user.deactivated', meta: [
                'before' => $before,
                'after' => ['is_active' => false],
            ]);
        });
    }
}
