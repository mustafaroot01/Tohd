<?php

namespace Database\Seeders;

use App\Enums\CurriculumStatus;
use App\Models\Curriculum;
use App\Models\Game;
use Illuminate\Database\Seeder;

class DemoCurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $curriculum = Curriculum::updateOrCreate(
            ['code' => 'CURR-001'],
            [
                'name' => 'منهج الانطلاقة والتأسيس الشامل',
                'slug' => 'foundational-curriculum',
                'description' => 'منهج تدريبي تفاعلي متكامل لتطوير مهارات التركيز والتواصل البصري والتفاعل الاجتماعي للأطفال',
                'status' => CurriculumStatus::PUBLISHED,
                'version' => 1,
                'published_at' => now(),
            ]
        );

        // Month 1
        $month1 = $curriculum->months()->updateOrCreate(
            ['month_number' => 1],
            [
                'name' => 'الشهر الأول: بناء الانتباه والاستجابة الأولية',
                'description' => 'التركيز على مهارات التثبيت البصري والانتباه الانتقائي',
                'sort_order' => 1,
            ]
        );

        // Week 1
        $week1 = $month1->weeks()->updateOrCreate(
            ['week_number' => 1],
            [
                'name' => 'الأسبوع الأول: التحدي الاستكشافي',
                'description' => 'أنشطة يومية تفاعلية لتعزيز الانتباه والتتبع البصري',
                'sort_order' => 1,
            ]
        );

        $games = Game::published()->get()->keyBy('code');

        // 5 Days in Week 1
        $dailyPlan = [
            1 => ['name' => 'اليوم الأول: اكتشاف النجوم', 'game_codes' => ['ATT-001', 'EYE-001']],
            2 => ['name' => 'اليوم الثاني: تتبع الفراشة', 'game_codes' => ['TRK-001', 'DIS-001']],
            3 => ['name' => 'اليوم الثالث: مرآة المشاعر', 'game_codes' => ['SOC-001', 'ATT-001']],
            4 => ['name' => 'اليوم الرابع: نظرة الصداقة', 'game_codes' => ['EYE-001', 'TRK-001']],
            5 => ['name' => 'اليوم الخامس: التحدي الختامي للأسبوع', 'game_codes' => ['DIS-001', 'SOC-001', 'ATT-001']],
        ];

        foreach ($dailyPlan as $dayNum => $data) {
            $day = $week1->days()->updateOrCreate(
                ['day_number' => $dayNum],
                [
                    'name' => $data['name'],
                    'description' => "تمارين وألعاب اليوم {$dayNum}",
                    'estimated_duration_seconds' => 900,
                    'sort_order' => $dayNum,
                ]
            );

            foreach ($data['game_codes'] as $sortIdx => $gameCode) {
                if (isset($games[$gameCode])) {
                    $day->dayGames()->updateOrCreate(
                        ['game_id' => $games[$gameCode]->id],
                        [
                            'sort_order' => $sortIdx + 1,
                            'is_required' => true,
                        ]
                    );
                }
            }
        }
    }
}
