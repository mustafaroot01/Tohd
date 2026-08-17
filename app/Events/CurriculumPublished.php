<?php

namespace App\Events;

use App\Models\Curriculum;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurriculumPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Curriculum $curriculum) {}
}
