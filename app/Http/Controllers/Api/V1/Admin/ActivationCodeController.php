<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Activations\GenerateActivationCodesAction;
use App\Actions\Activations\RevokeActivationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\GenerateActivationCodesRequest;
use App\Http\Resources\Api\V1\ActivationCodeResource;
use App\Models\ActivationCode;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivationCodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivationCode::with(['product', 'user']);

        TableQuery::filters($query, $request, ['status', 'product_id']);

        TableQuery::search($query, $request, ['code']);

        TableQuery::sort($query, $request, ['code', 'status', 'activated_at', 'expires_at', 'created_at'], 'created_at');

        $codes = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: ActivationCodeResource::collection($codes),
            message: 'تم استرجاع قائمة أكواد التفعيل بنجاح',
            meta: TableQuery::meta($codes)
        );
    }

    public function generate(GenerateActivationCodesRequest $request, GenerateActivationCodesAction $action): JsonResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));
        $quantity = (int) $request->validated('quantity');
        $expiresAt = $request->filled('expires_at') ? Carbon::parse($request->validated('expires_at')) : null;

        $codes = $action->execute($product, $quantity, $expiresAt, $request->user());

        return ApiResponse::success(
            data: ActivationCodeResource::collection($codes),
            message: "تم توليد ({$quantity}) كود تفعيل بنجاح",
            status: Response::HTTP_CREATED
        );
    }

    public function show(ActivationCode $activation): JsonResponse
    {
        $activation->load(['product', 'user']);

        return ApiResponse::success(
            data: new ActivationCodeResource($activation),
            message: 'تم استرجاع تفاصيل كود التفعيل بنجاح'
        );
    }

    public function revoke(ActivationCode $activation, RevokeActivationAction $action, Request $request): JsonResponse
    {
        $revoked = $action->execute($activation, $request->user());

        return ApiResponse::success(
            data: new ActivationCodeResource($revoked->fresh(['product', 'user'])),
            message: 'تم إلغاء كود التفعيل بنجاح'
        );
    }
}
