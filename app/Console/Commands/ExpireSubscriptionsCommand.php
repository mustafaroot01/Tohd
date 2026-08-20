<?php

namespace App\Console\Commands;

use App\Enums\AssignmentStatus;
use App\Enums\SubscriberActivityType;
use App\Models\UserCurriculumAssignment;
use App\Services\SubscriberActivityLogger;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'app:expire-subscriptions';
    protected $description = 'تحويل الاشتراكات المنتهية الصلاحية من فعال إلى منتهٍ';

    public function handle(SubscriberActivityLogger $activityLogger): int
    {
        $assignments = UserCurriculumAssignment::where('status', AssignmentStatus::ACTIVE)
            ->where('ends_at', '<', now())
            ->with('user')
            ->get();

        $this->info("تم العثور على ({$assignments->count()}) اشتراك منتهي الصلاحية...");

        foreach ($assignments as $assignment) {
            $assignment->update(['status' => AssignmentStatus::EXPIRED]);

            if ($assignment->user) {
                $activityLogger->log($assignment->user, SubscriberActivityType::SUBSCRIPTION_EXPIRED, [
                    'assignment_id' => $assignment->id,
                    'curriculum_id' => $assignment->curriculum_id,
                ]);
            }

            $this->line("<info>✔</info> تم تحويل الاشتراك {$assignment->id} إلى منتهي الصلاحية");
        }

        $this->info('تمت معالجة الاشتراكات المنتهية بنجاح!');

        return Command::SUCCESS;
    }
}
