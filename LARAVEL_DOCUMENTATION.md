# Laravel Developer Documentation

A comprehensive guide to understanding Laravel concepts used in this SocialBlog application. Use this document to learn and explain these concepts for your CV/resume.

---

## Table of Contents

1. [Laravel Framework Basics](#1-laravel-framework-basics)
2. [Authentication & Authorization](#2-authentication--authorization)
3. [Database & ORM](#3-database--orm)
4. [API Development](#4-api-development)
5. [File Upload & Cloud Storage](#5-file-upload--cloud-storage)
6. [Caching & Performance](#6-caching--performance)
7. [Queues & Background Jobs](#7-queues--background-jobs)
8. [Error Handling & Validation](#8-error-handling--validation)
9. [Events & Listeners](#9-events--listeners)
10. [Rate Limiting](#10-rate-limiting)

---

## 1. Laravel Framework Basics

### What is Laravel?

Laravel is a PHP web framework known for its elegant syntax and powerful features. It follows the MVC (Model-View-Controller) pattern and provides tools for routing, database operations, authentication, caching, and more.

### Why Use Laravel?

- **Elegant Syntax**: Clean, readable code
- **Built-in Features**: Authentication, caching, queues, etc.
- **Active Community**: Extensive packages and documentation
- **Security**: Built-in protection against common vulnerabilities
- **Testing**: PHPUnit integration out of the box

### Key Concepts in This Project

#### Service Container
```php
// Laravel automatically resolves dependencies
public function __construct(MediaUploadService $mediaService)
{
    $this->mediaService = $mediaService;
}
```

**What it does**: The service container manages class dependencies. When Laravel sees a class needs `MediaUploadService`, it automatically creates and injects it.

**Why use it**: Makes code testable and loosely coupled.

#### Service Providers
```php
// app/Providers/AppServiceProvider.php
public function register()
{
    // Register services
}

public function boot()
{
    // Bootstrap services
}
```

**What it does**: Service providers are the central place to register services, event listeners, middleware, and routes.

**Why use it**: All bootstrapping happens here - it's Laravel's configuration system.

#### Facades
```php
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
```

**What it is**: Facades provide a static interface to classes available in the service container. They look like static calls but are actually proxy calls to underlying objects.

**Common facades used**:
- `Log` - Logging
- `DB` - Database operations
- `Cache` - Caching operations
- `Route` - Routing
- `Auth` - Authentication

**Why use it**: Clean, readable syntax while maintaining testability.

---

## 2. Authentication & Authorization

### Laravel Sanctum

**What it is**: Laravel Sanctum provides a lightweight authentication system for SPAs (Single Page Applications) and mobile applications using API tokens.

**How it works**:
```php
// Generate token for user
$token = $user->createToken('auth_token')->plainTextToken;

// Send token to client
return ['token' => $token];
```

**Client includes token in requests**:
```
Authorization: Bearer 1|laravel_sanctum_token_xyz
```

**Why use Sanctum**:
- Simple token-based auth (vs OAuth2 with Passport)
- Tokens don't expire by default (configurable)
- Perfect for mobile/SPA apps
- Stateless authentication

### Implementation in This Project

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
});
```

### Key Authentication Concepts

#### Token Creation
```php
// Create a new token with a name
$token = $user->createToken('auth_token')->plainTextToken;

// Get all tokens
$tokens = $user->tokens;

// Revoke specific token
$token->delete();

// Revoke all tokens
$user->tokens()->delete();
```

#### Token Guard
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'sanctum' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

### Brute Force Protection

**What it is**: Prevents attackers from guessing passwords by limiting login attempts.

**Implementation**:
```php
class AuthController extends Controller
{
    protected const MAX_ATTEMPTS = 5;
    protected const LOCKOUT_MINUTES = 15;

    protected function throttleKey(Request $request): string
    {
        return 'login_attempts_' . strtolower($request->input('email'));
    }

    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        $key = $this->throttleKey($request);
        $attempts = cache($key, 0);
        return $attempts >= self::MAX_ATTEMPTS;
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        $key = $this->throttleKey($request);
        $attempts = cache($key, 0) + 1;
        cache([$key => $attempts], self::LOCKOUT_MINUTES * 60);
    }
}
```

**Why it matters**: Shows understanding of security best practices.

---

## 3. Database & ORM

### Eloquent ORM

**What it is**: Laravel's active record ORM (Object-Relational Mapping). Each database table has a corresponding "Model" that interacts with that table.

### Model Basics

```php
// app/Models/User.php
class User extends Model
{
    // Define fillable fields (mass assignment protection)
    protected $fillable = ['name', 'email', 'password'];
    
    // Define hidden fields (won't be returned in arrays/JSON)
    protected $hidden = ['password'];
    
    // Define relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

### Database Relationships

#### One-to-Many
```php
// User has many Posts
public function posts()
{
    return $this->hasMany(Post::class);
}

// Post belongs to User
public function user()
{
    return $this->belongsTo(User::class);
}
```

#### Many-to-Many (for Follow System)
```php
// User follows many users
public function following()
{
    return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
}

// Users follow this user (followers)
public function followers()
{
    return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
}
```

### Query Builder

**What it is**: A fluent interface for building database queries.

```php
// Basic queries
$users = User::where('active', true)->get();
$user = User::find(1);
$count = User::where('vip', true)->count();

// Aggregates
$total = DB::table('orders')->sum('amount');
$average = DB::table('orders')->avg('amount');

// Joins
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name')
    ->get();
```

### Eloquent Query Methods

```php
// Get single record
$post = Post::find(1);

// Get with relationships (eager loading)
$posts = Post::with(['user', 'comments', 'likes'])->get();

// Where clauses
$posts = Post::where('user_id', 1)
    ->where('published', true)
    ->orderBy('created_at', 'desc')
    ->paginate(15);

// Aggregates
$count = $post->likes()->count();
$exists = Post::where('user_id', 1)->exists();
```

### Database Migrations

**What it is**: Version control for your database schema.

```php
// Create users table
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

// Create posts table with foreign key
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('content');
    $table->string('image_url')->nullable();
    $table->timestamps();
});
```

**Key migration methods**:
- `id()` - Auto-incrementing primary key
- `foreignId()` - Creates foreign key column
- `constrained()` - References another table
- `onDelete('cascade')` - Delete related records
- `timestamps()` - Adds created_at and updated_at
- `softDeletes()` - Adds deleted_at for soft deletes

### Database Transactions

**What it is**: A sequence of operations that either all succeed or all fail.

```php
// Use when multiple operations must succeed together
DB::transaction(function () {
    $post->increment('likes_count');
    Like::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);
});
```

**Why use it**: Prevents partial updates if something fails midway.

---

## 4. API Development

### RESTful API Basics

REST (Representational State Transfer) is an architectural pattern for designing networked applications.

### API Endpoints Structure

```http
GET    /api/v1/posts           # List all posts
POST   /api/v1/posts            # Create new post
GET    /api/v1/posts/{id}       # Get single post
PUT    /api/v1/posts/{id}       # Update post
DELETE /api/v1/posts/{id}       # Delete post
```

### Route Groups & Prefixes

```php
// Group routes with common configuration
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
});
```

### API Resources

**What it is**: Laravel's way to transform Eloquent models into JSON responses.

```php
// Create a resource
php artisan make:resource PostResource

// app/Http/Resources/PostResource.php
class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'user' => new UserResource($this->user),
            'likes_count' => $this->likes->count(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

// Use in controller
return PostResource::collection($posts);
```

### API Response Format

**Best practice**: Consistent response structure

```php
// Success response
return response()->json([
    'success' => true,
    'message' => 'Operation successful',
    'data' => $resource,
], 200);

// Error response
return response()->json([
    'success' => false,
    'message' => 'Operation failed',
    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
], 500);
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST |
| 400 | Bad Request | Invalid input |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | No permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal error |

---

## 5. File Upload & Cloud Storage

### Cloudinary Integration

**What is Cloudinary**: A cloud-based image and video management service that provides:
- Image storage
- Automatic optimization
- CDN delivery
- Image transformations

### Upload Process

```php
// Validate file first
$validationResult = $this->validateFile($file);
if ($validationResult !== true) {
    return ['error' => $validationResult];
}

// Upload to Cloudinary
$result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
    'folder' => 'socialblog/posts',
    'public_id' => $uniqueId,
    'resource_type' => 'image',
    'transformation' => [
        'quality' => 'auto',
        'fetch_format' => 'auto',
    ],
]);

return [
    'url' => $result['secure_url'],
    'public_id' => $result['public_id'],
];
```

### File Validation

**Why validate**: Security and user experience

```php
public function validateFile(UploadedFile $file): bool|string
{
    // Check file is valid upload
    if (!$file->isValid()) {
        return 'File upload failed';
    }

    // Check size (5MB max)
    $maxSize = 5120 * 1024; // 5MB in bytes
    if ($file->getSize() > $maxSize) {
        return 'File too large. Max 5MB allowed.';
    }

    // Check MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file->getMimeType(), $allowedTypes)) {
        return 'Invalid file type';
    }

    // Check image dimensions
    $imageInfo = @getimagesize($file->getRealPath());
    if ($imageInfo && ($imageInfo[0] > 4096 || $imageInfo[1] > 4096)) {
        return 'Image too large. Max 4096x4096 pixels.';
    }

    return true;
}
```

### Cloudinary Transformations

**What they are**: On-the-fly image modifications

```php
// Get transformed URL
$thumbnail = $cloudinary->image($publicId)
    ->resize(Resize::fill()->width(300)->height(300))
    ->delivery(Delivery::quality('auto'))
    ->toUrl();

$medium = $cloudinary->image($publicId)
    ->resize(Resize::limitFit()->width(800)->height(800))
    ->delivery(Delivery::quality('auto'))
    ->toUrl();
```

**Benefits**:
- Serve optimized images automatically
- Generate multiple sizes from one upload
- Reduce bandwidth and improve loading speed

---

## 6. Caching & Performance

### What is Caching?

Caching stores frequently accessed data in fast storage (memory) to reduce database queries and improve response times.

### Cache Drivers

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
    'database' => [
        'driver' => 'database',
        'table' => 'cache',
    ],
],
```

### Cache Operations

```php
use Illuminate\Support\Facades\Cache;

// Store value
Cache::put('key', 'value', 300); // 5 minutes
Cache::put('key', 'value', now()->addMinutes(5));

// Get value
$value = Cache::get('key', 'default');
Cache::get('key', function () {
    return $this->expensiveOperation();
});

// Check if exists
if (Cache::has('key')) { ... }

// Remember (get or set)
$users = Cache::remember('users', 300, function () {
    return User::all();
});

// Delete
Cache::forget('key');
Cache::flush(); // Clear all
```

### Cache Tags

```php
// Tag items for group clearing
Cache::tags(['posts', 'user_1'])->put('key', 'value', 300);
Cache::tags(['posts'])->flush(); // Clear all tagged posts
```

### Cache Service with Fallback

```php
class CacheService
{
    protected const FALLBACK_DRIVER = 'file';

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            // Fallback to file-based cache
            return $this->fallbackGet($key, $default);
        }
    }
}
```

**Why use fallback**: If Redis goes down, your app still works with slower but reliable file cache.

### Feed Caching Example

```php
public function getPersonalizedFeed(User $user, int $limit = 15): array
{
    $cacheKey = "feed:user:{$user->id}:limit:{$limit}";

    return Cache::remember($cacheKey, 300, function () use ($user, $limit) {
        return $this->buildFeed($user, $limit);
    });
}
```

**Benefits**:
- Reduces database load
- Faster response times
- Improves user experience

---

## 7. Queues & Background Jobs

### What are Queues?

Queues allow you to defer time-consuming tasks (sending emails, processing images, generating reports) to be processed in the background.

### Why Use Queues?

- Improve response time to users
- Handle heavy processing without blocking
- Retry failed jobs automatically
- Distribute work across workers

### Creating a Job

```php
// php artisan make:job ProcessPostImage

class ProcessPostImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $maxBackoff = 60;

    protected Post $post;
    protected string $tempFilePath;

    public function __construct(Post $post, string $tempFilePath)
    {
        $this->post = $post;
        $this->tempFilePath = $tempFilePath;
        $this->onQueue('media'); // Dedicated queue
    }

    public function handle(MediaUploadService $mediaService): void
    {
        // Process the image
        $result = $mediaService->uploadImage($this->tempFilePath);
        
        if ($result['success']) {
            $this->post->update(['image_url' => $result['url']]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        // Called when all retries are exhausted
        Log::error('Job failed permanently', [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Dispatching a Job

```php
// Dispatch to queue
ProcessPostImageJob::dispatch($post, $tempFilePath)
    ->onQueue('media')
    ->delay(now()->addSeconds(10));

// Or chain with other jobs
ProcessPostImageJob::dispatch($post, $path)
    ->onQueue('media')
    ->timeout(120)
    ->retryAfter(30);
```

### Queue Configuration

```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
    ],
],
```

### Queue Commands

```bash
# Start worker (process jobs from queue)
php artisan queue:work

# Process single job then exit
php artisan queue:work --once

# Listen for jobs (restarts on failure)
php artisan queue:listen

# Retry failed jobs
php artisan queue:retry all
php artisan queue:retry 1,2,3

# View failed jobs
php artisan queue:failed

# Flush failed jobs
php artisan queue:flush
```

### Exponential Backoff

```php
public function backoff(): array
{
    // Wait 10s, then 30s, then 60s between retries
    return [10, 30, 60];
}
```

**Why use backoff**: Prevents overwhelming services when they're experiencing issues.

---

## 8. Error Handling & Validation

### Form Requests (Validation)

**What it is**: Dedicated request classes for validation

```php
// php artisan make:request StorePostRequest

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or use policies: $this->user()->can('create', Post::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|min:3',
            'content' => 'required|string|min:10',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a title',
            'title.min' => 'Title must be at least 3 characters',
            'content.required' => 'Please write some content',
        ];
    }
}
```

**Use in controller**:
```php
public function store(StorePostRequest $request)
{
    // Validation passed, safe to use
    $data = $request->validated();
    // ...
}
```

### Exception Handling

```php
// app/Exceptions/Handler.php

public function register(): void
{
    $this->reportable(function (\Throwable $e) {
        // Log the exception
        Log::error('Unhandled exception', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    });
}

public function render($request, \Throwable $e)
{
    if ($e instanceof ValidationException) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    }

    return response()->json([
        'success' => false,
        'message' => 'Server error',
    ], 500);
}
```

### Try-Catch Patterns

```php
public function like(Request $request, Post $post): JsonResponse
{
    try {
        // Perform operation
        $like = Like::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $like,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Like failed', [
            'error' => $e->getMessage(),
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to like post',
            'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
        ], 500);
    }
}
```

---

## 9. Events & Listeners

### What are Events?

Events are a way to signal that something happened in your application. Listeners respond to these events.

### Creating Events and Listeners

```bash
php artisan make:event PostLiked
php artisan make:listener HandlePostLiked --event=PostLiked
```

### Event Class

```php
class PostLiked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Post $post;
    public User $user;

    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }
}
```

### Firing Events

```php
// In your controller
use App\Events\PostLiked;

public function like(Request $request, Post $post): JsonResponse
{
    // ... create like ...
    
    // Fire event
    event(new PostLiked($post, $request->user()));
    
    return response()->json(['success' => true]);
}
```

### Event Listener

```php
class HandlePostLiked
{
    public function handle(PostLiked $event): void
    {
        $post = $event->post;
        $user = $event->user;

        // Send notification
        Notification::send($post->user, new PostLikedNotification($user));

        // Update activity feed
        Activity::create([
            'user_id' => $post->user_id,
            'type' => 'post_liked',
            'data' => ['post_id' => $post->id, 'liked_by' => $user->id],
        ]);

        // Update real-time stats
        Redis::incr("post:{$post->id}:likes");
    }
}
```

### Event Service Provider

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    PostLiked::class => [
        HandlePostLiked::class,
        UpdateLikeCount::class,
    ],
];
```

### Why Use Events?

- **Decoupling**: Components don't need to know about each other
- **Extensibility**: Add listeners without modifying existing code
- **Multiple Responses**: One event can trigger multiple actions
- **Audit Trail**: Track what happened in your system

---

## 10. Rate Limiting

### What is Rate Limiting?

Rate limiting controls how many requests a user can make in a given time period.

### Laravel Rate Limiter

```php
// Define rate limiters
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

RateLimiter::for('write', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
```

### Applying Rate Limits to Routes

```php
// Apply to single route
Route::post('/posts', [PostController::class, 'store'])
    ->middleware('throttle:write');

// Apply to route group
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});
```

### Rate Limit Response

When limit is exceeded, Laravel returns 429:

```json
{
    "message": "Too Many Attempts.",
    "retry_after": 60
}
```

### Customizing Rate Limit Response

```php
// In RouteServiceProvider
public function boot(): void
{
    parent::boot();

    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip())
            ->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests',
                    'retry_after' => 60,
                ], 429);
            });
    });
}
```

### Why Rate Limiting Matters

- **Security**: Prevents brute force attacks
- **Performance**: Protects server resources
- **Fair Usage**: Ensures all users get fair access
- **Cost Control**: Reduces API costs for paid services

---

## Summary: Key Concepts for Your CV

### Must-Have Skills to Highlight

1. **Laravel Framework**
   - MVC architecture
   - Routing, Controllers, Middleware
   - Eloquent ORM and relationships

2. **API Development**
   - RESTful API design
   - JWT/Sanctum authentication
   - API Resources and transformers

3. **Database**
   - MySQL/PostgreSQL
   - Database migrations
   - Query optimization

4. **Security**
   - Authentication & Authorization
   - Input validation
   - Rate limiting
   - SQL injection prevention (Eloquent handles this)

5. **Performance**
   - Caching strategies
   - Queue jobs
   - Database indexing

### Example CV Bullet Points

✅ **Implemented RESTful API with Laravel Sanctum authentication, handling 10,000+ daily requests**

✅ **Designed database schema with optimized relationships (User → Posts → Comments → Likes)**

✅ **Built image upload system with Cloudinary integration and automatic optimization**

✅ **Implemented queue-based background job processing with Redis, reducing response times by 80%**

✅ **Added comprehensive rate limiting and brute force protection for authentication endpoints**

✅ **Developed personalized feed system with Redis caching, serving 500ms response times**

✅ **Created event-driven architecture for notifications and activity tracking**

---

## Recommended Learning Path

1. **Beginner**
   - Learn PHP OOP fundamentals
   - Understand MVC pattern
   - Build simple CRUD app

2. **Intermediate**
   - Laravel routing and controllers
   - Eloquent relationships
   - Authentication systems

3. **Advanced**
   - Queue jobs and events
   - Caching strategies
   - API design best practices
   - Performance optimization

### Useful Resources

- **Official Docs**: https://laravel.com/docs
- **Laracasts**: https://laracasts.com (video tutorials)
- **PHP.NET**: https://www.php.net/docs.php

---

*Document generated for SocialBlog Laravel Application*
*Use this as reference for learning and CV writing*
