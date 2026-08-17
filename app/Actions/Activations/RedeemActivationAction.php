<?php

namespace App\Actions\Activations;

use App\Enums\ActivationStatus;
use App\Enums\AssignmentStatus;
use App\Events\ActivationRedeemed;
use App\Exceptions\ActivationAlreadyUsedException;
use App\Exceptions\ActivationExpiredException;
use App\Exceptions\CurriculumNotPublishedException;
use App\Exceptions\InvalidActivationCodeException;
use App\Exceptions\ProductInactiveException;
use App\Models\ActivationCode;
use App\Models\User;
use App\Models\UserCurriculumAssignment;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class RedeemActivationAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Redeem an activation code atomically with row-locking to prevent race conditions.
     *
     * @throws InvalidActivationCodeException
     * @throws ActivationAlreadyUsedException
     * @throws ActivationExpiredException
     * @throws ProductInactiveException
     * @throws CurriculumNotPublishedException
     */
    public function execute(string $codeString, User $user): UserCurriculumAssignment
    {
        $normalizedCode = strtoupper(trim($codeString));

        return DB::transaction(function () use ($normalizedCode, $user) {
            // Lock the activation code row for update
            $activation = ActivationCode::where('code', $normalizedCode)
                ->lockForUpdate()
                ->first();

            if (! $activation) {
                throw new InvalidActivationCodeException('كود التفعيل المدخل غير موجود في النظام.');
            }

            if ($activation->status === ActivationStatus::ACTIVATED) {
                throw new ActivationAlreadyUsedException('تم تفعيل هذا الكود مسبقاً.');
            }

            if ($activation->status === ActivationStatus::EXPIRED || ($activation->expires_at && $activation->expires_at->isPast())) {
                throw new ActivationExpiredException('كود التفعيل منتهي الصلاحية.');
            }

            if ($activation->status !== ActivationStatus::AVAILABLE) {
                throw new InvalidActivationCodeException('كود التفعيل غير صالح للاستخدام.');
            }

            $product = $activation->product;
            if (! $product || ! $product->isActive()) {
                throw new ProductInactiveException('المنتج المرتبط بهذا الكود غير متاح حالياً.');
            }

            $curriculum = $product->curriculum;
            if (! $curriculum || ! $curriculum->isPublished()) {
                throw new CurriculumNotPublishedException('المنهج المرتبط بالمنتج غير منشور حالياً.');
            }

            $startsAt = now();
            $durationDays = max(1, $product->duration_days);
            $endsAt = now()->addDays($durationDays);

            // Update activation record
            $activation->update([
                'status' => ActivationStatus::ACTIVATED,
                'activated_by' => $user->id,
                'activated_at' => $startsAt,
            ]);

            // Create assignment
            $assignment = UserCurriculumAssignment::create([
                'user_id' => $user->id,
                'curriculum_id' => $curriculum->id,
                'activation_id' => $activation->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AssignmentStatus::ACTIVE,
            ]);

            $this->auditLog->log(
                'ACTIVATION_REDEEMED',
                'ActivationCode',
                $activation->id,
                null,
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'curriculum_id' => $curriculum->id,
                    'assignment_id' => $assignment->id,
                ],
                $user
            );

            event(new ActivationRedeemed($user, $activation, $product, $curriculum, $assignment));

            return $assignment->load(['curriculum', 'activation.product']);
        });
    }
}
