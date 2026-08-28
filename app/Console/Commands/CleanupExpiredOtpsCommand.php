<?php

namespace App\Console\Commands;

use App\Models\PhoneVerification;
use Illuminate\Console\Command;

class CleanupExpiredOtpsCommand extends Command
{
    protected $signature = 'app:cleanup-expired-otps {--days=1 : عمر السجلات بالأيام قبل الحذف}';

    protected $description = 'حذف سجلات رموز التحقق المنتهية (phone_verifications)';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = PhoneVerification::where('expires_at', '<', now()->subDays($days))->delete();

        $this->info("تم حذف ({$deleted}) سجل تحقق منتهٍ أقدم من {$days} يوم.");

        return Command::SUCCESS;
    }
}
