<?php

namespace App\Policies;

use App\Models\Curriculum;
use App\Models\User;

class CurriculumPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Curriculum $curriculum): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $curriculum->isPublished();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin();
    }
}
