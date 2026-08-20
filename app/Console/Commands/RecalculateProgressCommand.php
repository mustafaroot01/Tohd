<?php

namespace App\Console\Commands;

use App\Models\Skill;
use App\Models\Subscriber;
use App\Services\ProgressCalculationService;
use Illuminate\Console\Command;

class RecalculateProgressCommand extends Command
{
    protected $signature = 'app:recalculate-progress {user_id? : معرف المستخدم الاختياري}';
    protected $description = 'إعادة احتساب وتحديث سجلات تقدم المهارات لجميع المستخدمين';

    public function handle(ProgressCalculationService $progressService): int
    {
        $userId = $this->argument('user_id');
        $users = $userId ? Subscriber::where('id', $userId)->get() : Subscriber::all();
        $skills = Skill::all();

        $this->info("بدء إعادة احتساب التقدم لعدد ({$users->count()}) مستخدم...");

        foreach ($users as $user) {
            foreach ($skills as $skill) {
                $progressService->updateSkillProgressForUser($user, $skill);
            }
            $this->line("<info>✔</info> تم تحديث تقدم المستخدم: {$user->name} ({$user->phone})");
        }

        $this->info('تمت إعادة احتساب وتحديث سجلات التقدم بنجاح!');

        return Command::SUCCESS;
    }
}
