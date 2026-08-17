<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            AxisSeeder::class,
            SkillSeeder::class,
            DemoGameSeeder::class,
            DemoCurriculumSeeder::class,
            DemoProductSeeder::class,
            DemoActivationSeeder::class,
        ]);
    }
}
