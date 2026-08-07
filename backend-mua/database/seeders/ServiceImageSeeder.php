<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Database\Seeder;

class ServiceImageSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::all();

        foreach ($services as $index => $service) {
            ServiceImage::create([
                'service_id' => $service->id,
                'image_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80',
                'image_source' => 'external',
                'sort_order' => 0,
                'is_cover' => true,
            ]);

            ServiceImage::create([
                'service_id' => $service->id,
                'image_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=800&q=80',
                'image_source' => 'external',
                'sort_order' => 1,
                'is_cover' => false,
            ]);
        }
    }
}
