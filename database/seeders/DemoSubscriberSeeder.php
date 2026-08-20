<?php

namespace Database\Seeders;

use App\Enums\SubscriberStatus;
use App\Models\Subscriber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSubscriberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subscriber::updateOrCreate(
            ['phone' => '+9647701234567'],
            [
                'name' => 'فارس البطل (User)',
                'address' => 'بغداد، العراق',
                'password' => Hash::make('user123456'),
                'status' => SubscriberStatus::ACTIVE,
                'phone_verified_at' => now(),
            ]
        );
    }
}
