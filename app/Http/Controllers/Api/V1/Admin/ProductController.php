<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Products\CreateProductAction;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('curriculum');

        TableQuery::filters($query, $request, ['status', 'curriculum_id']);

        TableQuery::search($query, $request, ['name', 'code']);

        TableQuery::sort($query, $request, ['name', 'code', 'price', 'duration_days', 'status', 'created_at'], 'created_at');

        $products = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: ProductResource::collection($products),
            message: 'تم استرجاع قائمة المنتجات بنجاح',
            meta: TableQuery::meta($products)
        );
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute($request->validated(), $request->user());

        return ApiResponse::success(
            data: new ProductResource($product->load('curriculum')),
            message: 'تم إنشاء المنتج بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('curriculum');

        return ApiResponse::success(
            data: new ProductResource($product),
            message: 'تم استرجاع تفاصيل المنتج بنجاح'
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return ApiResponse::success(
            data: new ProductResource($product->fresh('curriculum')),
            message: 'تم تحديث بيانات المنتج بنجاح'
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف المنتج بنجاح'
        );
    }

    public function activate(Product $product): JsonResponse
    {
        $product->update(['status' => ProductStatus::ACTIVE]);

        return ApiResponse::success(
            data: new ProductResource($product),
            message: 'تم تفعيل المنتج بنجاح'
        );
    }

    public function deactivate(Product $product): JsonResponse
    {
        $product->update(['status' => ProductStatus::INACTIVE]);

        return ApiResponse::success(
            data: new ProductResource($product),
            message: 'تم إيقاف المنتج بنجاح'
        );
    }
}
