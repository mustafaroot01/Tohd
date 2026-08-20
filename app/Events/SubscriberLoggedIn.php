<?php

namespace App\Events;

use App\Models\Subscriber;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriberLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(public Subscriber $user) {}
}
