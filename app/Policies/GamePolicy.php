<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Game $game): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $game->isPlayable();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }

    public function publish(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }
}
