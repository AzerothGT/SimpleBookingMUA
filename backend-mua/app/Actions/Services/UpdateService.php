<?php

namespace App\Actions\Services;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateService
{
    public function handle(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data): Service {
            $service = Service::query()->lockForUpdate()->findOrFail($service->id);

            if (array_key_exists('is_active', $data)
                && ! (bool) $data['is_active']
                && $service->bookings()->active()->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => 'Services with active bookings cannot be deactivated.',
                ]);
            }

            $service->update($data);

            return $service->refresh();
        });
    }
}
