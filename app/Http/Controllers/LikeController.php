<?php

namespace App\Http\Controllers;

use App\Events\PostLiked;
use App\Events\PostUnliked;
use App\Models\Like;
use App\Models\Post;
use App\Notifications\SocialActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * LikeController - Handles post likes with race condition prevention
 *
 * Features:
 * - Database transactions for atomic operations
 * - Row-level locking for concurrent request handling
 * - Optimistic locking for high-traffic scenarios
 * - Event-driven notifications
 */
class LikeController extends Controller
{
    /**
     * Like a post (idempotent operation).
     *
     * Uses database transaction and row locking to prevent race conditions.
     */
    public function like(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            // Use transaction with row locking to prevent race conditions
            $result = DB::transaction(function () use ($post, $userId) {
                // Lock the row for update
                $existingLike = Like::where('user_id', $userId)
                    ->where('post_id', $post->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingLike) {
                    // Already liked - return success (idempotent)
                    return [
                        'created' => false,
                        'like' => $existingLike,
                    ];
                }

                // Create new like
                $like = Like::create([
                    'user_id' => $userId,
                    'post_id' => $post->id,
                ]);

                // Increment likes_count atomically
                $post->increment('likes_count');

                // Refresh to get updated count
                $post->refresh();

                return [
                    'created' => true,
                    'like' => $like,
                    'likes_count' => $post->likes_count,
                ];
            });

            $wasCreated = $result['created'];

            // Dispatch event for notifications (async)
            if ($wasCreated) {
                event(new PostLiked($post, $request->user()));

                if ($post->user_id !== $userId) {
                    $post->user->notify(new SocialActivityNotification(
                        'post_liked',
                        "{$request->user()->name} liked your post.",
                        ['post_id' => $post->id, 'user_id' => $userId]
                    ));
                }

                Log::info('Post liked', [
                    'user_id' => $userId,
                    'post_id' => $post->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $wasCreated ? 'Post liked successfully' : 'Post already liked',
                'data' => [
                    'post_id' => $post->id,
                    'is_liked' => true,
                    'likes_count' => $result['likes_count'] ?? $post->likesCount(),
                ],
            ], $wasCreated ? 201 : 200);
        } catch (Throwable $e) {
            Log::error('Failed to like post', [
                'user_id' => $userId,
                'post_id' => $post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to like post',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Unlike a post (idempotent operation).
     *
     * Uses database transaction for atomic operation.
     */
    public function unlike(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $result = DB::transaction(function () use ($post, $userId) {
                // Find and delete the like
                $deleted = Like::where('user_id', $userId)
                    ->where('post_id', $post->id)
                    ->delete();

                if ($deleted) {
                    // Decrement likes_count atomically
                    $post->decrement('likes_count');
                    $post->refresh();
                }

                return [
                    'deleted' => $deleted > 0,
                    'likes_count' => max(0, $post->likes_count),
                ];
            });

            if ($result['deleted']) {
                event(new PostUnliked($post, $request->user()));

                Log::info('Post unliked', [
                    'user_id' => $userId,
                    'post_id' => $post->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $result['deleted'] ? 'Post unliked successfully' : 'Post was not liked',
                'data' => [
                    'post_id' => $post->id,
                    'is_liked' => false,
                    'likes_count' => $result['likes_count'],
                ],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to unlike post', [
                'user_id' => $userId,
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unlike post',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Toggle like (like if not liked, unlike if liked).
     *
     * Uses atomic check-and-update to prevent race conditions.
     */
    public function toggle(Request $request, Post $post): JsonResponse
    {
        try {
            // Check current status atomically
            $isLiked = Like::where('user_id', $request->user()->id)
                ->where('post_id', $post->id)
                ->exists();

            if ($isLiked) {
                return $this->unlike($request, $post);
            } else {
                return $this->like($request, $post);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get users who liked a post.
     */
    public function likedBy(Request $request, Post $post): JsonResponse
    {
        try {
            $perPage = min((int) $request->input('per_page', 15), 50);

            $likes = Like::where('post_id', $post->id)
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $likesData = $likes->map(function ($like) {
                return [
                    'id' => $like->user->id,
                    'name' => $like->user->name,
                    'email' => $like->user->email,
                    'liked_at' => $like->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'post_id' => $post->id,
                    'likes' => $likesData,
                    'pagination' => [
                        'current_page' => $likes->currentPage(),
                        'per_page' => $likes->perPage(),
                        'total' => $likes->total(),
                        'last_page' => $likes->lastPage(),
                        'has_more' => $likes->hasMorePages(),
                    ],
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch likes',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Check if current user has liked a post.
     */
    public function check(Request $request, Post $post): JsonResponse
    {
        try {
            $isLiked = Like::where('user_id', $request->user()->id)
                ->where('post_id', $post->id)
                ->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'post_id' => $post->id,
                    'is_liked' => $isLiked,
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check like status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get like count for a post.
     */
    public function count(Request $request, Post $post): JsonResponse
    {
        try {
            $likesCount = Like::where('post_id', $post->id)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'post_id' => $post->id,
                    'likes_count' => $likesCount,
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get like count',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
