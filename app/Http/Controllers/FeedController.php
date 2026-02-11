<?php

namespace App\Http\Controllers;

use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FeedController extends Controller
{
    protected FeedService $feedService;

    public function __construct(FeedService $feedService)
    {
        $this->feedService = $feedService;
    }

    /**
     * Get personalized feed for authenticated user.
     * Shows posts from users they follow, ordered by recency.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function personalFeed(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
                'cursor' => ['nullable', 'integer', 'exists:posts,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $limit = $request->input('limit', 15);
            $cursor = $request->input('cursor');

            $feedData = $this->feedService->getPersonalizedFeed(
                $request->user(),
                $limit,
                $cursor
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $feedData['posts'],
                    'pagination' => [
                        'next_cursor' => $feedData['next_cursor'],
                        'has_more' => $feedData['has_more'],
                        'limit' => $limit,
                    ],
                ],
                'meta' => [
                    'feed_type' => 'personalized',
                    'cached' => true, // Indicates response may be cached
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch personalized feed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch feed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get public/trending feed.
     * Shows all posts ordered by engagement score.
     * Available to both authenticated and unauthenticated users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicFeed(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
                'cursor' => ['nullable', 'integer', 'exists:posts,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $limit = $request->input('limit', 15);
            $cursor = $request->input('cursor');

            $feedData = $this->feedService->getPublicFeed(
                $limit,
                $cursor,
                $request->user() // May be null for unauthenticated users
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $feedData['posts'],
                    'pagination' => [
                        'next_cursor' => $feedData['next_cursor'],
                        'has_more' => $feedData['has_more'],
                        'limit' => $limit,
                    ],
                ],
                'meta' => [
                    'feed_type' => 'public',
                    'cached' => true,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch public feed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch feed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get feed statistics for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function feedStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->feedService->getFeedStats($request->user());

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch feed stats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch feed statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Refresh feed cache for authenticated user.
     * Useful when user knows there should be new content.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshFeed(Request $request): JsonResponse
    {
        try {
            // Invalidate user's feed cache
            $this->feedService->invalidateUserFeedCache($request->user()->id);

            // Fetch fresh feed
            $feedData = $this->feedService->getPersonalizedFeed(
                $request->user(),
                15,
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'Feed refreshed successfully',
                'data' => [
                    'posts' => $feedData['posts'],
                    'pagination' => [
                        'next_cursor' => $feedData['next_cursor'],
                        'has_more' => $feedData['has_more'],
                        'limit' => 15,
                    ],
                ],
                'meta' => [
                    'feed_type' => 'personalized',
                    'cached' => false, // Fresh data
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to refresh feed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh feed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
