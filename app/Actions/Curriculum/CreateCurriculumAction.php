<?php

namespace App\Actions\Curriculum;

use App\Enums\CurriculumStatus;
use App\Models\Curriculum;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Str;

class CreateCurriculumAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function execute(array $data, ?User $creator = null): Curriculum
    {
        $curriculum = Curriculum::create([
            'code' => $data['code'] ?? 'CUR-'.strtoupper(Str::random(6)),
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4),
            'description' => $data['description'] ?? null,
            'status' => CurriculumStatus::DRAFT,
            'version' => 1,
            'created_by' => $creator?->id,
            'updated_by' => $creator?->id,
        ]);

        $this->auditLog->log('CURRICULUM_CREATED', 'Curriculum', $curriculum->id, null, $curriculum->toArray(), $creator);

        return $curriculum;
    }
}
