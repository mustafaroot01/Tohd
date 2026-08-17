<?php

namespace App\Actions\Curriculum;

use App\Models\CurriculumMonth;
use App\Models\CurriculumWeek;

class AddWeekAction
{
    public function execute(CurriculumMonth $month, array $data): CurriculumWeek
    {
        $nextNumber = $data['week_number'] ?? (($month->weeks()->max('week_number') ?? 0) + 1);

        return $month->weeks()->create([
            'week_number' => $nextNumber,
            'name' => $data['name'] ?? "الأسبوع {$nextNumber}",
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $nextNumber,
        ]);
    }
}
