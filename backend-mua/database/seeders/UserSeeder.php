<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'phone' => '081234567890',
            'password_hash' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'phone' => '081234567891',
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Staff One',
            'username' => 'staff1',
            'phone' => '081234567892',
            'password_hash' => Hash::make('password123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Staff Two',
            'username' => 'staff2',
            'phone' => '081234567893',
            'password_hash' => Hash::make('password123'),
            'role' => 'staff',
            'is_active' => true,
        ]);
    }
}
