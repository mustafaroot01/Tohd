<?php

namespace App\Console\Commands;

use App\Models\Otp;
use Illuminate\Console\Command;

class CleanupExpiredOtpsCommand extends Command
{
    protected $signature = 'app:cleanup-expired-otps {--days=30 : عمر السجلات بالأيام قبل الحذف}';
    protected $description = 'حذف رموز OTP منتهية الصلاحية أو مُستهلكة الأقدم من عدد أيام معين';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = Otp::where('expires_at', '<', now()->subDays($days))->delete();

        $this->info("تم حذف ({$deleted}) رمز تحقق منتهي الصلاحية أقدم من {$days} يوماً.");

        return Command::SUCCESS;
    }
}
