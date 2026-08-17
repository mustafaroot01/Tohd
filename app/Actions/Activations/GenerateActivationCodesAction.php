<?php

namespace App\Actions\Activations;

use App\Enums\ActivationStatus;
use App\Models\ActivationCode;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateActivationCodesAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Generate a batch of unique activation codes for a product.
     *
     * @return Collection<int, ActivationCode>
     */
    public function execute(Product $product, int $quantity = 1, ?Carbon $expiresAt = null, ?User $creator = null): Collection
    {
        $maxBatch = (int) config('activation.max_batch_quantity', 500);
        $quantity = min(max(1, $quantity), $maxBatch);

        return DB::transaction(function () use ($product, $quantity, $expiresAt, $creator) {
            $createdCodes = collect();

            for ($i = 0; $i < $quantity; $i++) {
                $codeString = $this->generateUniqueCodeString();

                $codeRecord = ActivationCode::create([
                    'code' => $codeString,
                    'product_id' => $product->id,
                    'status' => ActivationStatus::AVAILABLE,
                    'expires_at' => $expiresAt,
                ]);

                $createdCodes->push($codeRecord);
            }

            $this->auditLog->log(
                'ACTIVATION_GENERATED',
                'Product',
                $product->id,
                null,
                ['quantity' => $quantity, 'expires_at' => $expiresAt?->toISOString()],
                $creator
            );

            return $createdCodes;
        });
    }

    protected function generateUniqueCodeString(): string
    {
        do {
            $raw = strtoupper(Str::random(16));
            $formatted = implode('-', str_split($raw, 4));
        } while (ActivationCode::where('code', $formatted)->exists());

        return $formatted;
    }
}
