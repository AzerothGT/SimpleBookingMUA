<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Support\Facades\DB;

class SaveServiceImage
{
    public function create(Service $service, array $data): ServiceImage
    {
        return DB::transaction(function () use ($service, $data): ServiceImage {
            $service = Service::query()->lockForUpdate()->findOrFail($service->id);
            $this->clearCover($service, $data);

            return $service->serviceImages()->create($data);
        });
    }

    public function update(Service $service, ServiceImage $image, array $data): ServiceImage
    {
        return DB::transaction(function () use ($service, $image, $data): ServiceImage {
            $service = Service::query()->lockForUpdate()->findOrFail($service->id);
            $image = $service->serviceImages()->lockForUpdate()->findOrFail($image->id);
            $this->clearCover($service, $data);
            $image->update($data);

            return $image->refresh();
        });
    }

    private function clearCover(Service $service, array $data): void
    {
        if (($data['is_cover'] ?? false) === true) {
            $service->serviceImages()->lockForUpdate()->update(['is_cover' => false]);
        }
    }
}
