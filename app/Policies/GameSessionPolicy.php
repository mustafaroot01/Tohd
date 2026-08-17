<?php

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;

class GameSessionPolicy
{
    public function view(User $user, GameSession $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }

    public function complete(User $user, GameSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
