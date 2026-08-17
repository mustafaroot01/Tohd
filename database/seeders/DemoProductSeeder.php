<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Curriculum;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $curriculum = Curriculum::where('code', 'CURR-001')->first();

        if ($curriculum) {
            Product::updateOrCreate(
                ['code' => 'PRODUCT-001'],
                [
                    'name' => 'باقة رحلة فارس التأسيسية (30 يوماً)',
                    'slug' => 'foundational-package-30d',
                    'description' => 'اشتراك كامل لمدة شهر يتضمن كافة الألعاب التدريبية اليومية وتقارير التقدم المباشرة',
                    'curriculum_id' => $curriculum->id,
                    'duration_days' => 30,
                    'status' => ProductStatus::ACTIVE,
                    'price' => 199.00,
                    'currency' => 'SAR',
                    'metadata' => [
                        'features' => [
                            'تدريب يومي مخصص للأطفال',
                            'تقارير أداء ومتابعة لحظية',
                            'ألعاب تفاعلية برسومات Lottie متحركة',
                        ],
                    ],
                ]
            );
        }
    }
}
