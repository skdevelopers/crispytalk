<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
//    return view('welcome');
    return 'API is working web!';
});

// Public Routes (no authentication required)
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');

Route::get('/test-ffmpeg', function () {
    // Video path from public disk
    $videoPath = Storage::disk('public')->path('videos/sample.mp4');

    // Check if the file exists
    if (!file_exists($videoPath)) {
        return 'Video file does not exist!';
    }

    // Initialize FFMpeg instance
    $ffmpeg = FFMpeg::create();

    // Open the video file
    $video = $ffmpeg->open($videoPath);

    // Export the video to the desired format and save it to the same disk
    $outputPath = Storage::disk('public')->path('videos/output.mp4');
    $video->save(new X264(), $outputPath);

    return 'Video converted successfully!';
});

