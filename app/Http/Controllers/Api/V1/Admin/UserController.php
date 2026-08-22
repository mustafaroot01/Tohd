<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        TableQuery::filters($query, $request, ['status']);

        TableQuery::search($query, $request, ['name', 'email']);

        TableQuery::sort($query, $request, ['name', 'email', 'status', 'last_login_at', 'created_at'], 'created_at');

        $users = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: UserResource::collection($users),
            message: 'تم استرجاع قائمة مستخدمي النظام بنجاح',
            meta: TableQuery::meta($users)
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['auditLogs']);

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'تم استرجاع بيانات المستخدم بنجاح'
        );
    }
}
