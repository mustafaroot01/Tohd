<?php

namespace App\Policies;

use App\Models\ActivationCode;
use App\Models\User;

class ActivationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ActivationCode $activation): bool
    {
        return $user->isAdmin() || $activation->activated_by === $user->id;
    }

    public function generate(User $user): bool
    {
        return $user->isAdmin();
    }

    public function revoke(User $user, ActivationCode $activation): bool
    {
        return $user->isAdmin();
    }
}
