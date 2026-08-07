<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceImage>
 */
class ServiceImageFactory extends Factory
{
    public function definition(): array
    {
        $isExternal = fake()->boolean();

        return [
            'service_id' => Service::factory(),
            'image_url' => $isExternal
                ? fake()->imageUrl()
                : 'services/'.fake()->uuid().'.jpg',
            'image_source' => $isExternal ? 'external' : 'upload',
            'sort_order' => 0,
            'is_cover' => false,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cover' => true,
        ]);
    }
}
