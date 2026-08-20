<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $target): bool
    {
        return true;
    }

    public function update(User $user, User $target): bool
    {
        return true;
    }
}
