<?php

namespace App\Actions\Curriculum;

use App\Models\CurriculumDay;
use App\Models\CurriculumWeek;

class AddDayAction
{
    public function execute(CurriculumWeek $week, array $data): CurriculumDay
    {
        $nextNumber = $data['day_number'] ?? (($week->days()->max('day_number') ?? 0) + 1);

        return $week->days()->create([
            'day_number' => $nextNumber,
            'name' => $data['name'] ?? "اليوم {$nextNumber}",
            'description' => $data['description'] ?? null,
            'estimated_duration_seconds' => $data['estimated_duration_seconds'] ?? config('curriculum.default_day_duration_seconds', 900),
            'sort_order' => $data['sort_order'] ?? $nextNumber,
        ]);
    }
}
