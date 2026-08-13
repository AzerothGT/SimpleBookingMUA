<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects deactivation while active bookings exist', function (string $status) {
    $service = Service::factory()->create(['is_active' => true]);
    $booking = Booking::factory()->create(['status' => $status]);
    $booking->bookingServices()->create([
        'service_id' => $service->id,
        'qty' => 1,
    ]);

    $this->withToken(tokenForRole('admin'))
        ->patchJson('/api/services/'.$service->id, ['is_active' => false])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('is_active');

    expect($service->fresh()->is_active)->toBeTrue();
})->with(['pending', 'confirmed']);

it('treats zero as a deactivation request', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $booking = Booking::factory()->create(['status' => 'pending']);
    $booking->bookingServices()->create([
        'service_id' => $service->id,
        'qty' => 1,
    ]);

    $this->withToken(tokenForRole('admin'))
        ->patchJson('/api/services/'.$service->id, ['is_active' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('is_active');
});

it('allows deactivation when only inactive bookings exist', function (string $status) {
    $service = Service::factory()->create(['is_active' => true]);
    $booking = Booking::factory()->create(['status' => $status]);
    $booking->bookingServices()->create([
        'service_id' => $service->id,
        'qty' => 1,
    ]);

    $this->withToken(tokenForRole('admin'))
        ->patchJson('/api/services/'.$service->id, ['is_active' => false])
        ->assertSuccessful()
        ->assertJsonPath('is_active', false);
})->with(['done', 'cancelled']);

it('switches the service cover atomically', function () {
    $service = Service::factory()->create();
    $oldCover = ServiceImage::factory()->for($service)->cover()->create();
    $newCover = ServiceImage::factory()->for($service)->create();

    $this->withToken(tokenForRole('admin'))
        ->patchJson("/api/services/{$service->id}/serviceImages/{$newCover->id}", [
            'is_cover' => true,
        ])
        ->assertSuccessful();

    expect($oldCover->fresh()->is_cover)->toBeFalse()
        ->and($newCover->fresh()->is_cover)->toBeTrue()
        ->and($service->serviceImages()->where('is_cover', true)->count())->toBe(1);
});

it('rejects moving an image to another service', function () {
    $service = Service::factory()->create();
    $otherService = Service::factory()->create();
    $image = ServiceImage::factory()->for($service)->create();

    $this->withToken(tokenForRole('admin'))
        ->patchJson("/api/services/{$service->id}/serviceImages/{$image->id}", [
            'service_id' => $otherService->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('service_id');

    expect($image->fresh()->service_id)->toBe($service->id);
});
