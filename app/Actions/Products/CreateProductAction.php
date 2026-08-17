<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Str;

class CreateProductAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function execute(array $data, ?User $user = null): Product
    {
        $product = Product::create([
            'code' => $data['code'] ?? 'PRD-'.strtoupper(Str::random(6)),
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4),
            'description' => $data['description'] ?? null,
            'curriculum_id' => $data['curriculum_id'],
            'duration_days' => $data['duration_days'] ?? 30,
            'status' => $data['status'] ?? ProductStatus::ACTIVE,
            'price' => $data['price'] ?? 0.00,
            'currency' => $data['currency'] ?? 'SAR',
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->auditLog->log('PRODUCT_CREATED', 'Product', $product->id, null, $product->toArray(), $user);

        return $product;
    }
}
