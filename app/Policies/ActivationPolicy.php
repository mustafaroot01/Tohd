<?php

namespace App\Policies;

use App\Models\ActivationCode;
use App\Models\User;

class ActivationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivationCode $activation): bool
    {
        return true;
    }

    public function generate(User $user): bool
    {
        return true;
    }

    public function revoke(User $user, ActivationCode $activation): bool
    {
        return true;
    }
}
