<?php

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

it('keeps one cover during concurrent updates', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrent row-lock verification requires MySQL.');
    }

    $service = Service::factory()->create();
    $images = ServiceImage::factory()->for($service)->count(2)->create();

    try {
        $worker = base_path('tests/Support/set-service-cover.php');
        $processes = $images->map(fn (ServiceImage $image) => new Process([
            PHP_BINARY,
            $worker,
            $service->id,
            $image->id,
        ], base_path()));

        $processes->each->start();
        $processes->each->wait();

        expect($processes->map->isSuccessful()->all())->toBe([true, true])
            ->and($service->serviceImages()->where('is_cover', true)->count())->toBe(1);
    } finally {
        $service->serviceImages()->delete();
        $service->delete();
    }
});
