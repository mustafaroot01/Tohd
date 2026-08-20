<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'مدير النظام (Admin)',
                'password' => Hash::make('admin123456'),
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
    }
}
