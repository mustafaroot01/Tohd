<?php

namespace App\Events;

use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\UserCurriculumAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivationRedeemed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscriber $user,
        public ActivationCode $activation,
        public Product $product,
        public Curriculum $curriculum,
        public UserCurriculumAssignment $assignment
    ) {}
}
