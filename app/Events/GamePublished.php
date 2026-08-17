<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GamePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Game $game) {}
}
