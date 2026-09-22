<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ProfessionalProfileController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ProfessionalNetworkController;
use App\Http\Controllers\MentorshipController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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

// Configure rate limiters
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Stricter rate limiter for authentication endpoints
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

// Rate limiter for write operations (POST, PUT, DELETE)
RateLimiter::for('write', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});

// Rate limiter for feed operations
RateLimiter::for('feed', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes (stricter rate limiting)
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.password.email');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.password.reset');
    });

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    // Public post routes (anyone can view)
    Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.posts.show');

    // Public user profile and follow lists (anyone can view)
    Route::get('/users/{user}/profile', [FollowController::class, 'userProfile'])->name('api.users.profile');
    Route::get('/users/{user}/followers', [FollowController::class, 'followers'])->name('api.users.followers');
    Route::get('/users/{user}/following', [FollowController::class, 'following'])->name('api.users.following');
    Route::get('/users/{user}/professional-profile', [ProfessionalProfileController::class, 'show'])->name('api.professional-profile.show');
    Route::get('/users/{user}/endorsements', [ProfessionalNetworkController::class, 'endorsements'])->name('api.endorsements.index');
    Route::get('/users/{user}/recommendations', [ProfessionalNetworkController::class, 'recommendations'])->name('api.recommendations.index');

    // Public likes and comments (anyone can view)
    Route::get('/posts/{post}/likes', [LikeController::class, 'likedBy'])->name('api.posts.likes');
    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('api.posts.comments');

    // Public feed (trending/popular posts)
    Route::get('/feed/public', [FeedController::class, 'publicFeed'])->name('api.feed.public');
    Route::get('/groups', [GroupController::class, 'index'])->name('api.groups.index');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('api.groups.show');
    Route::get('/events', [EventController::class, 'index'])->name('api.events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('api.events.show');
    Route::get('/jobs', [JobController::class, 'index'])->name('api.jobs.index');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('api.jobs.show');
});

// Protected routes (requires authentication)
Route::prefix('v1')->middleware(['auth:sanctum', 'active'])->group(function () {
    // Authentication management
Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.logout.all');
        Route::get('/profile', [AuthController::class, 'profile'])->name('api.profile');
        Route::post('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');
    Route::get('/tokens', [AuthController::class, 'tokens'])->name('api.tokens');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->name('api.token.refresh');
    Route::delete('/tokens/{tokenId}', [AuthController::class, 'revokeToken'])->name('api.tokens.revoke');
    Route::delete('/revoke-all', [AuthController::class, 'revokeAll'])->name('api.revokeAll');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])->name('verification.send');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('api.password.change');
    Route::delete('/account', [AuthController::class, 'deleteAccount'])->name('api.account.delete');
    Route::get('/conversations', [MessagingController::class, 'conversations'])->name('api.conversations.index');
    Route::post('/conversations', [MessagingController::class, 'start'])->name('api.conversations.start');
    Route::get('/conversations/{conversation}/messages', [MessagingController::class, 'messages'])->name('api.conversations.messages');
    Route::post('/conversations/{conversation}/messages', [MessagingController::class, 'send'])->name('api.conversations.messages.send');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('api.notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('api.notifications.read');
    Route::post('/reports', [ModerationController::class, 'report'])->name('api.reports.store');
    Route::post('/users/{user}/block', [ModerationController::class, 'block'])->name('api.users.block');
    Route::delete('/users/{user}/block', [ModerationController::class, 'unblock'])->name('api.users.unblock');
    Route::get('/moderation/reports', [ModerationController::class, 'reports'])->name('api.moderation.reports');
    Route::patch('/moderation/reports/{report}', [ModerationController::class, 'reviewReport'])->name('api.moderation.reports.review');
    Route::post('/moderation/users/{user}/suspend', [ModerationController::class, 'suspend'])->name('api.moderation.users.suspend');
    Route::put('/professional-profile', [ProfessionalProfileController::class, 'update'])->name('api.professional-profile.update');
    Route::post('/professional-profile/entries', [ProfessionalProfileController::class, 'addEntry'])->name('api.professional-profile.entries.store');
    Route::delete('/professional-profile/entries/{entry}', [ProfessionalProfileController::class, 'deleteEntry'])->name('api.professional-profile.entries.destroy');
    Route::get('/my-applications', [JobController::class, 'myApplications'])->name('api.jobs.applications');
    Route::get('/mentorships', [MentorshipController::class, 'index'])->name('api.mentorships.index');
    Route::post('/mentorships/{mentorship}/respond', [MentorshipController::class, 'respond'])->name('api.mentorships.respond');
    Route::post('/mentorships/{mentorship}/cancel', [MentorshipController::class, 'cancel'])->name('api.mentorships.cancel');
    Route::get('/connections', [ProfessionalNetworkController::class, 'connections'])->name('api.connections.index');
    Route::post('/connections/{connection}/respond', [ProfessionalNetworkController::class, 'respond'])->name('api.connections.respond');
    Route::post('/recommendations/{recommendation}/respond', [ProfessionalNetworkController::class, 'respondRecommendation'])->name('api.recommendations.respond');

    // Write operations with rate limiting
    Route::middleware('throttle:write')->group(function () {
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
        Route::get('/posts/{post}/like/check', [LikeController::class, 'check'])->name('api.posts.like.check');
        Route::get('/posts/{post}/like/count', [LikeController::class, 'count'])->name('api.posts.like.count');

        // Comment system routes
        Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('api.posts.comments.store');
        Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('api.comments.update');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('api.comments.destroy');

        // Feed operations
        Route::post('/feed/refresh', [FeedController::class, 'refreshFeed'])->name('api.feed.refresh');
        Route::post('/groups', [GroupController::class, 'store'])->name('api.groups.store');
        Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('api.groups.join');
        Route::delete('/groups/{group}/leave', [GroupController::class, 'leave'])->name('api.groups.leave');
        Route::post('/events', [EventController::class, 'store'])->name('api.events.store');
        Route::post('/events/{event}/attend', [EventController::class, 'attend'])->name('api.events.attend');
        Route::delete('/events/{event}/leave', [EventController::class, 'leave'])->name('api.events.leave');
        Route::post('/jobs', [JobController::class, 'store'])->name('api.jobs.store');
        Route::post('/jobs/{job}/apply', [JobController::class, 'apply'])->name('api.jobs.apply');
        Route::post('/jobs/{job}/save', [JobController::class, 'save'])->name('api.jobs.save');
        Route::delete('/jobs/{job}/save', [JobController::class, 'unsave'])->name('api.jobs.unsave');
        Route::get('/jobs/{job}/applications', [JobController::class, 'applications'])->name('api.jobs.applications.index');
        Route::patch('/jobs/{job}/applications/{application}', [JobController::class, 'updateApplicationStatus'])->name('api.jobs.applications.status');
        Route::post('/jobs/{job}/close', [JobController::class, 'close'])->name('api.jobs.close');
        Route::post('/job-applications/{application}/withdraw', [JobController::class, 'withdraw'])->name('api.jobs.applications.withdraw');
        Route::post('/users/{user}/connect', [ProfessionalNetworkController::class, 'requestConnection'])->name('api.connections.request');
        Route::post('/users/{user}/endorsements', [ProfessionalNetworkController::class, 'endorse'])->name('api.endorsements.store');
        Route::post('/users/{user}/recommendations', [ProfessionalNetworkController::class, 'recommend'])->name('api.recommendations.store');
        Route::post('/users/{mentor}/mentorships', [MentorshipController::class, 'request'])->name('api.mentorships.request');
    });

    // Feed reading (higher rate limit)
    Route::middleware('throttle:feed')->group(function () {
        Route::get('/feed', [FeedController::class, 'personalFeed'])->name('api.feed.personal');
        Route::get('/feed/stats', [FeedController::class, 'feedStats'])->name('api.feed.stats');
    });
});

// Health check endpoint (public, no rate limiting)
Route::get('/health', function () {
    $status = 'ok';
    $checks = [];

    // Check database connection
    try {
        DB::connection()->getPdo();
        $checks['database'] = 'connected';
    } catch (\Exception $e) {
        $checks['database'] = 'error: ' . $e->getMessage();
        $status = 'degraded';
    }

    // Check cache connection
    try {
        Cache::get('health_check_key');
        $checks['cache'] = 'connected';
    } catch (\Exception $e) {
        $checks['cache'] = 'error: ' . $e->getMessage();
    }

    try {
        $failedJobs = (int) DB::table(config('queue.failed.table', 'failed_jobs'))->count();
        $checks['queue'] = [
            'driver' => config('queue.default'),
            'failed_jobs' => $failedJobs,
        ];
        if ($failedJobs > 0) {
            $status = 'degraded';
        }
    } catch (\Exception $e) {
        $checks['queue'] = 'error: ' . $e->getMessage();
        $status = 'degraded';
    }

    return response()->json([
        'status' => $status,
        'timestamp' => now()->toIso8601String(),
        'service' => 'SocialBlog API',
        'version' => '1.0.0',
        'checks' => $checks,
    ]);
})->name('api.health');
