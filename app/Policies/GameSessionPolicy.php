<?php

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;

class GameSessionPolicy
{
    public function view(User $user, GameSession $session): bool
    {
        return true;
    }

    public function complete(User $user, GameSession $session): bool
    {
        return $session->subscriber_id === $user->id;
    }
}
