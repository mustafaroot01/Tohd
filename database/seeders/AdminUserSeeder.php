<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        // Admin Account
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'مدير النظام (Admin)',
                'password' => Hash::make('admin123456'),
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        // Regular User Account
        User::updateOrCreate(
            ['email' => 'user@demo.com'],
            [
                'name' => 'فارس البطل (User)',
                'password' => Hash::make('user123456'),
                'role' => UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
    }
}
