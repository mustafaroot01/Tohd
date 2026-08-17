<?php

namespace App\Actions\Curriculum;

use App\Enums\CurriculumStatus;
use App\Events\CurriculumPublished;
use App\Models\Curriculum;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CurriculumValidationService;

class PublishCurriculumAction
{
    public function __construct(
        protected CurriculumValidationService $validator,
        protected AuditLogService $auditLog
    ) {}

    public function execute(Curriculum $curriculum, ?User $user = null): Curriculum
    {
        $this->validator->validate($curriculum);

        $oldValues = $curriculum->toArray();
        $curriculum->update([
            'status' => CurriculumStatus::PUBLISHED,
            'published_at' => now(),
            'updated_by' => $user?->id,
        ]);

        $this->auditLog->log('CURRICULUM_PUBLISHED', 'Curriculum', $curriculum->id, $oldValues, $curriculum->fresh()->toArray(), $user);

        event(new CurriculumPublished($curriculum));

        return $curriculum;
    }
}
