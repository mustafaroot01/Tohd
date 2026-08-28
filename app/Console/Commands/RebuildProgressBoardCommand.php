<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ProgressBoardService;
use Illuminate\Console\Command;

class RebuildProgressBoardCommand extends Command
{
    protected $signature = 'app:rebuild-progress-board {--subscriber= : rebuild one subscriber only}';

    protected $description = 'إعادة بناء لوحة تقدّم الألعاب (subscriber_game_progress) من السجل اليومي (subscriber_game_daily)';

    public function handle(ProgressBoardService $board): int
    {
        $query = Subscriber::query();
        if ($id = $this->option('subscriber')) {
            $query->whereKey($id);
        }

        $subscribers = 0;
        $rows = 0;

        $query->chunkById(200, function ($chunk) use ($board, &$subscribers, &$rows) {
            foreach ($chunk as $subscriber) {
                $rows += $board->rebuildFor($subscriber);
                $subscribers++;
            }
        });

        $this->info("أُعيد بناء اللوحة لـ {$subscribers} مشترك من {$rows} صف يومي.");

        return self::SUCCESS;
    }
}
