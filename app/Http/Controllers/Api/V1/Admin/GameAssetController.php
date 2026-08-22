<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AssetRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Models\Game;
use App\Models\GameAsset;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GameAssetController extends Controller
{
    /**
     * List all assets attached to a game
     */
    public function index(Game $game): JsonResponse
    {
        $game->load(['assets']);

        $assets = $game->assets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'code' => $asset->code,
                'name' => $asset->name,
                'type' => $asset->type?->value ?? (string) $asset->type,
                'mime_type' => $asset->mime_type,
                'url' => $asset->url,
                'role' => $asset->pivot?->role?->value ?? $asset->pivot?->role,
                'sort_order' => $asset->pivot?->sort_order,
                'metadata' => $asset->pivot?->metadata,
            ];
        });

        return ApiResponse::success(
            data: $assets,
            message: 'تم استرجاع ملفات اللعبة بنجاح'
        );
    }

    /**
     * Attach an existing asset to a game with a role
     */
    public function attach(Request $request, Game $game): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'role' => ['required', 'string', 'in:'.implode(',', array_column(AssetRole::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Prevent duplicate attachment of same asset+role
        $existing = $game->gameAssets()
            ->where('asset_id', $validated['asset_id'])
            ->where('role', $validated['role'])
            ->first();

        if ($existing) {
            return ApiResponse::error(
                message: 'هذا الملف مرتبط باللعبة بنفس الدور مسبقاً',
                errorCode: 'GAME_ASSET_ALREADY_LINKED',
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $gameAsset = GameAsset::create([
            'game_id' => $game->id,
            'asset_id' => $validated['asset_id'],
            'role' => $validated['role'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $asset = Asset::find($validated['asset_id']);

        return ApiResponse::success(
            data: [
                'game_asset_id' => $gameAsset->id,
                'asset' => new AssetResource($asset),
                'role' => $gameAsset->role?->value ?? $gameAsset->role,
                'sort_order' => $gameAsset->sort_order,
            ],
            message: 'تم ربط الملف باللعبة بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Update asset role/sort_order in a game
     */
    public function updateRole(Request $request, Game $game, GameAsset $gameAsset): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['sometimes', 'string', 'in:'.implode(',', array_column(AssetRole::cases(), 'value'))],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        $gameAsset->update($validated);

        return ApiResponse::success(
            data: [
                'game_asset_id' => $gameAsset->id,
                'role' => $gameAsset->fresh()->role?->value ?? $gameAsset->fresh()->role,
                'sort_order' => $gameAsset->fresh()->sort_order,
            ],
            message: 'تم تحديث دور الملف بنجاح'
        );
    }

    /**
     * Detach an asset from a game
     */
    public function detach(Game $game, GameAsset $gameAsset): JsonResponse
    {
        if ($gameAsset->game_id !== $game->id) {
            return ApiResponse::error(
                message: 'هذا الملف غير مرتبط بهذه اللعبة',
                errorCode: 'GAME_ASSET_NOT_LINKED',
                status: Response::HTTP_NOT_FOUND
            );
        }

        $gameAsset->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم إزالة الملف من اللعبة بنجاح'
        );
    }
}
