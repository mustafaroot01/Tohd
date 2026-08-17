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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Product::with('curriculum')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate($perPage);

        return ApiResponse::success(
            data: ProductResource::collection($products),
            message: 'تم استرجاع قائمة المنتجات بنجاح',
            meta: [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
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
