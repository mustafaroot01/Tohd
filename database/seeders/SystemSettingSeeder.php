<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['id' => 'default'],
            [
                'app_name' => 'رحلة فارس',
                'app_logo' => null,
                'is_maintenance' => false,
                'otp_enabled' => true,
            ]
        );
    }
}
