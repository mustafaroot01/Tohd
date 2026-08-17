<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    /**
     * Register a new user and generate a Sanctum token.
     *
     * @return array{user: User, token: string}
     */
    public function execute(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
            'last_login_at' => now(),
        ]);

        $token = $user->createToken('mobile_app')->plainTextToken;

        event(new UserRegistered($user));

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
