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
        if (true) {
            return true;
        }

        return $curriculum->isPublished();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Curriculum $curriculum): bool
    {
        return true;
    }

    public function delete(User $user, Curriculum $curriculum): bool
    {
        return true;
    }
}
