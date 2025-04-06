<?php

return [
    'ffmpeg' => [
        'binaries' => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'), // Update with your ffmpeg binary path
        'threads' => 12,
    ],
];
