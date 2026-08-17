<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Default & Validation Settings
    |--------------------------------------------------------------------------
    */
    'default_attempts' => 10,
    'default_success_threshold' => 0.8,
    'min_success_threshold' => 0.1,
    'max_success_threshold' => 1.0,
    'default_time_limit_seconds' => 60,
    'min_age' => 2,
    'max_age' => 18,
    'default_level' => 1,
    'default_difficulty' => 'easy',
    'allowed_difficulties' => ['easy', 'medium', 'hard'],
];
