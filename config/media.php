<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media & Asset Management Settings
    |--------------------------------------------------------------------------
    */
    'disk' => env('ASSET_DISK', 'public'),
    'max_lottie_size_kb' => 10240, // 10MB
    'max_image_size_kb' => 5120,   // 5MB
    'max_audio_size_kb' => 20480,  // 20MB
    'max_video_size_kb' => 102400, // 100MB
    'lottie_required_keys' => ['v', 'fr', 'ip', 'op', 'w', 'h', 'layers'],
];
