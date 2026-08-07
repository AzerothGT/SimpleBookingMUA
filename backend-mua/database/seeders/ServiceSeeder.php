<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::create([
            'name' => 'Makeup Natural',
            'price' => 500000,
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Makeup Party',
            'price' => 750000,
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Makeup Wedding',
            'price' => 1500000,
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Makeup Graduation',
            'price' => 600000,
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Makeup Photoshoot',
            'price' => 800000,
            'is_active' => true,
        ]);
    }
}
