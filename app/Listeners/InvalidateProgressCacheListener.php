<?php

namespace App\Listeners;

use App\Events\GameSessionCompleted;
use Illuminate\Support\Facades\Cache;

class InvalidateProgressCacheListener
{
    /**
     * Invalidate cached progress for user upon game completion.
     */
    public function handle(GameSessionCompleted $event): void
    {
        $userId = $event->session->user_id;
        Cache::forget("user:{$userId}:progress:overall");
        Cache::forget("user:{$userId}:progress:daily");
        Cache::forget("user:{$userId}:progress:weekly");
        Cache::forget("user:{$userId}:progress:monthly");
        Cache::forget("user:{$userId}:today_plan");
    }
}
