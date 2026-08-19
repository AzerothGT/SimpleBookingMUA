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

it('stores and returns the service description', function () {
    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/services', [
            'name' => 'Wedding Makeup',
            'description' => 'Riasan pengantin lengkap dengan touch-up.',
            'price' => 1_500_000,
        ])
        ->assertCreated()
        ->assertJsonPath('description', 'Riasan pengantin lengkap dengan touch-up.');
});

it('includes inactive services for authenticated owner and admin', function (string $role) {
    $active = Service::factory()->create(['is_active' => true]);
    $inactive = Service::factory()->inactive()->create();

    $this->withToken(tokenForRole($role))
        ->getJson('/api/services?include_inactive=1')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $active->id])
        ->assertJsonFragment(['id' => $inactive->id]);
})->with(['owner', 'admin']);

it('hides inactive services from staff even with include_inactive', function () {
    $active = Service::factory()->create(['is_active' => true]);
    $inactive = Service::factory()->inactive()->create();

    $this->withToken(tokenForRole('staff'))
        ->getJson('/api/services?include_inactive=1')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $active->id])
        ->assertJsonMissing(['id' => $inactive->id]);
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

it('accepts an external https image URL', function () {
    $service = Service::factory()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson("/api/services/{$service->id}/serviceImages", [
            'image_url' => 'https://images.example.test/service.jpg',
            'image_source' => 'external',
        ])
        ->assertCreated()
        ->assertJsonPath('image_url', 'https://images.example.test/service.jpg');
});

it('rejects uploaded service images', function () {
    $service = Service::factory()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson("/api/services/{$service->id}/serviceImages", [
            'image_url' => 'https://images.example.test/service.jpg',
            'image_source' => 'upload',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image_source');
});

it('rejects non-http external image URLs', function () {
    $service = Service::factory()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson("/api/services/{$service->id}/serviceImages", [
            'image_url' => 'javascript:alert(1)',
            'image_source' => 'external',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image_url');
});
