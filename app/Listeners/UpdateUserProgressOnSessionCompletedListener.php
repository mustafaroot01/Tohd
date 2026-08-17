<?php

namespace App\Listeners;

use App\Events\GameSessionCompleted;
use App\Services\ProgressCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUserProgressOnSessionCompletedListener implements ShouldQueue
{
    public function __construct(
        protected ProgressCalculationService $progressService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GameSessionCompleted $event): void
    {
        $session = $event->session;
        $user = $session->user;
        $game = $session->game;

        if ($user && $game && $game->skill) {
            $this->progressService->updateSkillProgressForUser($user, $game->skill);
        }
    }
}
