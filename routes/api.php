<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| assigned the "api" middleware group.
|
*/

// Public Routes (no authentication required)
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');

// Authenticated Routes (require auth:sanctum middleware)
Route::middleware('auth:sanctum')->group(function () {

    // User Actions
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    // User Management
    Route::get('/user', [UserController::class, 'profile'])->name('user.profile');
    Route::get('/users', [UserController::class, 'getAllUsers'])->name('user.all');
    Route::get('/user/{id}', [UserController::class, 'getUserById'])
        ->where('id', '[0-9]+');
    Route::put('/user/update', [UserController::class, 'updateProfile']);
    Route::delete('/user/delete', [UserController::class, 'deleteAccount']);
    Route::post('/user/upload-images', [UserController::class, 'uploadImages']);

    // Comments API
    Route::post('/comments/add', [CommentController::class, 'addComment']);
    Route::get('/comments/{postId}', [CommentController::class, 'getComments']);
    Route::delete('/comments/{id}', [CommentController::class, 'deleteComment']);

    // Define static routes first:
    Route::get('/posts/my', [UserController::class, 'getUserPosts']);
    Route::get('/posts/user/{userId}', [UserController::class, 'getUserProfilePosts']);
    Route::get('/posts/friends', [UserController::class, 'friendsFeed']);
    Route::get('/posts/home', [PostController::class, 'homeFeed']);

    // Post Actions
    Route::get('/posts', [PostController::class, 'index']); // Get all posts
    Route::post('/posts', [PostController::class, 'store']); // Create a post
    Route::get('/posts/{id}', [PostController::class, 'show'])->where('id', '[0-9]+'); // Get a single post
    Route::put('/posts/{id}', [PostController::class, 'update']); // Update a post
    Route::delete('/posts/{id}', [PostController::class, 'destroy']); // Delete a post


// Dynamic route that only matches numeric IDs.
    Route::get('/videos/{post}', [PostController::class, 'showVideo']);
    Route::get('/videos/{postId}/paths', [PostController::class, 'fetchVideoPaths']);
    Route::get('/user/videos', [PostController::class, 'listUserVideos']);
    Route::get('/users/{userId}/videos', [PostController::class, 'listUserProfileVideos']);


    // userNotification Actions
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notification.send');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notification.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notification.read');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Chat Actions
    Route::get('/chats', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chats', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chat.show');
    // Chat Media
    Route::post('/chats/upload-media', [ChatController::class, 'uploadMedia']);

    // Message routes (Use MessageController)
    Route::get('/chats/{chat}/messages', [MessageController::class, 'index']); // Fetch messages
    Route::post('/chats/{chat}/messages', [MessageController::class, 'store'])->name('chat.message.send'); // Send message

    // Call endpoints
    Route::post('/calls/initiate', [CallController::class, 'initiate'])->name('calls.initiate');
    Route::patch('/calls/{id}/accept', [CallController::class, 'accept'])->name('calls.accept');
    Route::patch('/calls/{id}/reject', [CallController::class, 'reject'])->name('calls.reject');
    Route::patch('/calls/{id}/end', [CallController::class, 'end'])->name('calls.end');
    Route::get('/calls/{id}', [CallController::class, 'show'])->name('calls.show');
    Route::get('/calls', [CallController::class, 'index'])->name('calls.index');
});
