<?php

namespace App\Actions\Services;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateService
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Service $service, User $actor, array $data): Service
    {
        return DB::transaction(function () use ($service, $actor, $data): Service {
            $service = Service::query()->lockForUpdate()->findOrFail($service->id);

            if (array_key_exists('is_active', $data)
                && ! (bool) $data['is_active']
                && $service->bookings()->active()->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => 'Services with active bookings cannot be deactivated.',
                ]);
            }

            $before = $service->only(array_keys($data));
            $service->update($data);

            $this->recordActivity->handle(
                $actor,
                $service,
                'service.updated',
                meta: [
                    'before' => $before,
                    'after' => $service->only(array_keys($data)),
                ],
                detail: 'Service details updated.',
            );

            return $service->refresh();
        });
    }
}
