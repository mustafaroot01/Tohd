<?php

namespace App\Actions\Curriculum;

use App\Models\Curriculum;
use App\Models\CurriculumMonth;

class AddMonthAction
{
    public function execute(Curriculum $curriculum, array $data): CurriculumMonth
    {
        $nextNumber = $data['month_number'] ?? (($curriculum->months()->max('month_number') ?? 0) + 1);

        return $curriculum->months()->create([
            'month_number' => $nextNumber,
            'name' => $data['name'] ?? "الشهر {$nextNumber}",
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $nextNumber,
        ]);
    }
}
