<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LikeController extends Controller
{
    /**
     * Like a post (idempotent operation).
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function like(Request $request, Post $post): JsonResponse
    {
        try {
            // Use firstOrCreate for idempotency - safe to call multiple times
            $like = Like::firstOrCreate([
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
            ]);

            // Check if like was just created or already existed
            $wasCreated = $like->wasRecentlyCreated;

            if ($wasCreated) {
                Log::info('Post liked', [
                    'user_id' => $request->user()->id,
                    'post_id' => $post->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $wasCreated ? 'Post liked successfully' : 'Post already liked',
                'data' => [
                    'post_id' => $post->id,
                    'is_liked' => true,
                    'likes_count' => $post->likesCount(),
                ],
            ], $wasCreated ? 201 : 200);
        } catch (\Exception $e) {
            Log::error('Failed to like post', [
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
                'error' => $e->getMessage(),
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
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function unlike(Request $request, Post $post): JsonResponse
    {
        try {
            // Delete if exists - safe to call even if not liked
            $deleted = Like::where('user_id', $request->user()->id)
                ->where('post_id', $post->id)
                ->delete();

            if ($deleted) {
                Log::info('Post unliked', [
                    'user_id' => $request->user()->id,
                    'post_id' => $post->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $deleted ? 'Post unliked successfully' : 'Post was not liked',
                'data' => [
                    'post_id' => $post->id,
                    'is_liked' => false,
                    'likes_count' => $post->likesCount(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to unlike post', [
                'user_id' => $request->user()->id,
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
     * Get users who liked a post.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function likedBy(Request $request, Post $post): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $perPage = min($perPage, 50);

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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch likes',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Toggle like (like if not liked, unlike if liked).
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function toggle(Request $request, Post $post): JsonResponse
    {
        try {
            $isLiked = $post->isLikedBy($request->user()->id);

            if ($isLiked) {
                return $this->unlike($request, $post);
            } else {
                return $this->like($request, $post);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
