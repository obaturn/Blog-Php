<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
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

    // Public likes and comments (anyone can view)
    Route::get('/posts/{post}/likes', [LikeController::class, 'likedBy'])->name('api.posts.likes');
    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('api.posts.comments');

    // Public feed (trending/popular posts)
    Route::get('/feed/public', [FeedController::class, 'publicFeed'])->name('api.feed.public');
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
    Route::post('/users/{user}/follow', [FollowController::class, 'follow'])->name('api.users.follow');
    Route::delete('/users/{user}/unfollow', [FollowController::class, 'unfollow'])->name('api.users.unfollow');
    Route::get('/follow/stats', [FollowController::class, 'stats'])->name('api.follow.stats');

    // Like system routes
    Route::post('/posts/{post}/like', [LikeController::class, 'like'])->name('api.posts.like');
    Route::delete('/posts/{post}/unlike', [LikeController::class, 'unlike'])->name('api.posts.unlike');
    Route::post('/posts/{post}/toggle-like', [LikeController::class, 'toggle'])->name('api.posts.toggleLike');

    // Comment system routes
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('api.posts.comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('api.comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('api.comments.destroy');

    // Feed system routes
    Route::get('/feed', [FeedController::class, 'personalFeed'])->name('api.feed.personal');
    Route::get('/feed/stats', [FeedController::class, 'feedStats'])->name('api.feed.stats');
    Route::post('/feed/refresh', [FeedController::class, 'refreshFeed'])->name('api.feed.refresh');

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
