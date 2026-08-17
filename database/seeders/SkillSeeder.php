<?php

namespace Database\Seeders;

use App\Models\Axis;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attention = Axis::where('slug', 'attention')->first();
        $eyeContact = Axis::where('slug', 'eye-contact')->first();
        $visualTracking = Axis::where('slug', 'visual-tracking')->first();
        $social = Axis::where('slug', 'social-interaction')->first();

        $skills = [
            // Attention Skills
            [
                'axis_id' => $attention?->id,
                'name' => 'الانتباه الانتقائي',
                'slug' => 'selective-attention',
                'description' => 'التركيز على عنصر محدد وسط مشتتات',
                'status' => 'ACTIVE',
                'sort_order' => 1,
            ],
            [
                'axis_id' => $attention?->id,
                'name' => 'الانتباه المستمر',
                'slug' => 'sustained-attention',
                'description' => 'المحافظة على التركيز لفترة زمنية محددة',
                'status' => 'ACTIVE',
                'sort_order' => 2,
            ],
            // Eye Contact Skills
            [
                'axis_id' => $eyeContact?->id,
                'name' => 'تثبيت النظر',
                'slug' => 'gaze-fixation',
                'description' => 'تثبيت النظر على وجوه أو شخصيات متحركة',
                'status' => 'ACTIVE',
                'sort_order' => 1,
            ],
            // Visual Tracking Skills
            [
                'axis_id' => $visualTracking?->id,
                'name' => 'التتبع الأفقي والعمودي',
                'slug' => 'linear-tracking',
                'description' => 'ملاحقة الأجسام المتحركة في مسارات مستقيمة ومنحنية',
                'status' => 'ACTIVE',
                'sort_order' => 1,
            ],
            // Social Interaction Skills
            [
                'axis_id' => $social?->id,
                'name' => 'تمييز المشاعر وتعبيرات الوجه',
                'slug' => 'emotion-recognition',
                'description' => 'التعرف على الفرح والحزن والمشاعر الأساسية',
                'status' => 'ACTIVE',
                'sort_order' => 1,
            ],
        ];

        foreach ($skills as $skillData) {
            if ($skillData['axis_id']) {
                Skill::updateOrCreate(['slug' => $skillData['slug']], $skillData);
            }
        }
    }
}
