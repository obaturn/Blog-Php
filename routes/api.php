<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');

    // Public post routes (anyone can view)
    Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.posts.show');

    // Public user profile and follow lists (anyone can view)
    Route::get('/users/{user}/profile', [FollowController::class, 'userProfile'])->name('api.users.profile');
    Route::get('/users/{user}/followers', [FollowController::class, 'followers'])->name('api.users.followers');
    Route::get('/users/{user}/following', [FollowController::class, 'following'])->name('api.users.following');
});

// Protected routes (requires authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('api.profile');
    Route::post('/revoke-all', [AuthController::class, 'revokeAll'])->name('api.revokeAll');

    // Post management routes
    Route::post('/posts', [PostController::class, 'store'])->name('api.posts.store');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('api.posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('api.posts.destroy');
    Route::get('/my-posts', [PostController::class, 'myPosts'])->name('api.posts.my');

    // Follow system routes
    Route::post('/users/{user}/follow', [\App\Http\Controllers\FollowController::class, 'follow'])->name('api.users.follow');
    Route::delete('/users/{user}/unfollow', [\App\Http\Controllers\FollowController::class, 'unfollow'])->name('api.users.unfollow');
    Route::get('/follow/stats', [\App\Http\Controllers\FollowController::class, 'stats'])->name('api.follow.stats');

    // Rate limiting applied
    Route::middleware('throttle:60,1')->group(function () {
        // Additional rate-limited routes can go here
    });
});

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'SocialBlog API',
        'version' => '1.0.0',
    ]);
})->name('api.health');
