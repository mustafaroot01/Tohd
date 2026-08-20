<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success(
            data: [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'status'        => $user->status,
                'last_login_at' => $user->last_login_at,
                'created_at'    => $user->created_at,
            ],
            message: 'تم استرجاع بيانات الحساب بنجاح'
        );
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password'      => ['nullable', 'string'],
            'password'              => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Verify current password if changing password
        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return ApiResponse::error('كلمة المرور الحالية غير صحيحة', 422);
            }
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['current_password']);

        $user->update($validated);

        return ApiResponse::success(
            data: [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            message: 'تم تحديث بيانات الحساب بنجاح'
        );
    }
}
