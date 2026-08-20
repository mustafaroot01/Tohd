<?php

namespace App\Actions\Auth;

use App\Models\Subscriber;
use App\Models\User;

class LogoutUserAction
{
    /**
     * Revoke current Sanctum token or all tokens for the authenticated user.
     */
    public function execute(User|Subscriber $user, bool $allDevices = false): bool
    {
        if ($allDevices) {
            $user->tokens()->delete();
        } else {
            $user->currentAccessToken()?->delete();
        }

        return true;
    }
}
