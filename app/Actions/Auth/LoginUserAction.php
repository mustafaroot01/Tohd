<?php

namespace App\Actions\Auth;

use App\Events\UserLoggedIn;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    /**
     * Authenticate a system user (admin) and issue a Sanctum token.
     *
     * @return array{user: User, token: string}
     *
     * @throws DomainException
     */
    public function execute(string $email, string $password, ?string $deviceName = 'admin_dashboard'): array
    {
        $user = User::where('email', strtolower(trim($email)))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new DomainException('بيانات الدخول غير صحيحة، يرجى التأكد من البريد الإلكتروني وكلمة المرور', 'INVALID_CREDENTIALS', 401);
        }

        if (! $user->isActive()) {
            throw new DomainException('تم تجميد أو إيقاف هذا الحساب، يرجى مراجعة الإدارة', 'ACCOUNT_SUSPENDED', 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken($deviceName ?: 'admin_dashboard')->plainTextToken;

        event(new UserLoggedIn($user));

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
