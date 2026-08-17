<?php

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Models\Axis;
use App\Models\Game;
use App\Models\Skill;
use App\Services\GameConfigurationBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configBuilder = new GameConfigurationBuilder;

        $attentionAxis = Axis::where('slug', 'attention')->first();
        $eyeContactAxis = Axis::where('slug', 'eye-contact')->first();
        $trackingAxis = Axis::where('slug', 'visual-tracking')->first();
        $socialAxis = Axis::where('slug', 'social-interaction')->first();

        $selectiveSkill = Skill::where('slug', 'selective-attention')->first();
        $sustainedSkill = Skill::where('slug', 'sustained-attention')->first();
        $gazeSkill = Skill::where('slug', 'gaze-fixation')->first();
        $linearSkill = Skill::where('slug', 'linear-tracking')->first();
        $emotionSkill = Skill::where('slug', 'emotion-recognition')->first();

        $games = [
            [
                'code' => 'ATT-001',
                'name' => 'صيد النجوم اللامعة',
                'slug' => 'star-catching',
                'description' => 'انقر على النجوم الذهبية اللامعة متجاهلاً السحب الداكنة',
                'type' => GameType::TAP,
                'axis_id' => $attentionAxis?->id,
                'skill_id' => $selectiveSkill?->id,
                'level' => 1,
                'difficulty' => 'easy',
                'min_age' => 3,
                'max_age' => 8,
                'duration_seconds' => 60,
                'status' => GameStatus::PUBLISHED,
                'version' => 1,
                'published_at' => now(),
                'config' => $configBuilder->build(GameType::TAP, [
                    'attempts' => 10,
                    'success_threshold' => 0.8,
                    'time_limit_seconds' => 60,
                    'reward' => ['type' => 'star', 'value' => 3],
                ]),
            ],
            [
                'code' => 'DIS-001',
                'name' => 'المكتشف الصغير',
                'slug' => 'little-discoverer',
                'description' => 'اختر العنصر المختلف من بين مجموعة الأشكال المتشابهة',
                'type' => GameType::CHOOSE,
                'axis_id' => $attentionAxis?->id,
                'skill_id' => $sustainedSkill?->id,
                'level' => 1,
                'difficulty' => 'easy',
                'min_age' => 3,
                'max_age' => 8,
                'duration_seconds' => 60,
                'status' => GameStatus::PUBLISHED,
                'version' => 1,
                'published_at' => now(),
                'config' => $configBuilder->build(GameType::CHOOSE, [
                    'attempts' => 8,
                    'success_threshold' => 0.75,
                    'time_limit_seconds' => 60,
                    'reward' => ['type' => 'badge', 'value' => 1],
                ]),
            ],
            [
                'code' => 'TRK-001',
                'name' => 'رحلة الفراشة الملونة',
                'slug' => 'butterfly-journey',
                'description' => 'تتبع حركة الفراشة بعينيك وهي تطير بين الأزهار الجميلة',
                'type' => GameType::TRACKING,
                'axis_id' => $trackingAxis?->id,
                'skill_id' => $linearSkill?->id,
                'level' => 1,
                'difficulty' => 'easy',
                'min_age' => 3,
                'max_age' => 10,
                'duration_seconds' => 60,
                'status' => GameStatus::PUBLISHED,
                'version' => 1,
                'published_at' => now(),
                'config' => $configBuilder->build(GameType::TRACKING, [
                    'attempts' => 5,
                    'success_threshold' => 0.8,
                    'time_limit_seconds' => 60,
                    'reward' => ['type' => 'gem', 'value' => 2],
                ]),
            ],
            [
                'code' => 'EYE-001',
                'name' => 'نظرة الصديق الوفي',
                'slug' => 'friend-gaze',
                'description' => 'ثبت نظرك على عيني الشخصية اللطيفة عندما تبتسم لك',
                'type' => GameType::TAP,
                'axis_id' => $eyeContactAxis?->id,
                'skill_id' => $gazeSkill?->id,
                'level' => 1,
                'difficulty' => 'easy',
                'min_age' => 3,
                'max_age' => 8,
                'duration_seconds' => 60,
                'status' => GameStatus::PUBLISHED,
                'version' => 1,
                'published_at' => now(),
                'config' => $configBuilder->build(GameType::TAP, [
                    'attempts' => 6,
                    'success_threshold' => 0.8,
                    'time_limit_seconds' => 60,
                    'reward' => ['type' => 'star', 'value' => 2],
                ]),
            ],
            [
                'code' => 'SOC-001',
                'name' => 'مرآة المشاعر والابتسامة',
                'slug' => 'emotions-mirror',
                'description' => 'تعرف على الوجه المبتسم والسعيد من بين تعابير الوجوه',
                'type' => GameType::EMOTION,
                'axis_id' => $socialAxis?->id,
                'skill_id' => $emotionSkill?->id,
                'level' => 1,
                'difficulty' => 'easy',
                'min_age' => 4,
                'max_age' => 12,
                'duration_seconds' => 60,
                'status' => GameStatus::PUBLISHED,
                'version' => 1,
                'published_at' => now(),
                'config' => $configBuilder->build(GameType::EMOTION, [
                    'attempts' => 6,
                    'success_threshold' => 0.8,
                    'time_limit_seconds' => 60,
                    'reward' => ['type' => 'trophy', 'value' => 1],
                ]),
            ],
        ];

        foreach ($games as $gameData) {
            if ($gameData['axis_id'] && $gameData['skill_id']) {
                Game::updateOrCreate(['code' => $gameData['code']], $gameData);
            }
        }
    }
}
