<?php

namespace Database\Seeders;

use App\Models\Axis;
use Illuminate\Database\Seeder;

class AxisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $axes = [
            [
                'name' => 'التركيز والانتباه',
                'slug' => 'attention',
                'description' => 'تطوير قدرات التركيز والاستجابة للمثيرات السمعية والبصرية',
                'status' => 'ACTIVE',
                'sort_order' => 1,
            ],
            [
                'name' => 'التواصل البصري',
                'slug' => 'eye-contact',
                'description' => 'تعزيز مهارات تثبيت النظر والتواصل البصري المباشر',
                'status' => 'ACTIVE',
                'sort_order' => 2,
            ],
            [
                'name' => 'التتبع البصري',
                'slug' => 'visual-tracking',
                'description' => 'تدريب العين على ملاحقة الأجسام المتحركة بسلاسة',
                'status' => 'ACTIVE',
                'sort_order' => 3,
            ],
            [
                'name' => 'التفاعل الاجتماعي',
                'slug' => 'social-interaction',
                'description' => 'تمييز تعابير الوجه والمشاعر والتفاعل والمشاركة الإيجابية',
                'status' => 'ACTIVE',
                'sort_order' => 4,
            ],
        ];

        foreach ($axes as $axisData) {
            Axis::updateOrCreate(['slug' => $axisData['slug']], $axisData);
        }
    }
}
