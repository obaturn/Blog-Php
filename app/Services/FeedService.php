<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedService
{
    /**
     * Cache TTL for feed results (in seconds).
     */
    protected int $cacheTtl;

    /**
     * Maximum number of posts to fetch per query.
     */
    protected int $maxPosts;

    public function __construct()
    {
        $this->cacheTtl = config('feed.cache_ttl', 300); // 5 minutes default
        $this->maxPosts = config('feed.max_posts', 50);
    }

    /**
     * Get personalized feed for a user (Fan-out on Read).
     * Shows posts from users they follow, ordered by recency.
     *
     * @param User $user
     * @param int $limit
     * @param int|null $cursor Post ID to paginate from (older posts)
     * @return array
     */
    public function getPersonalizedFeed(User $user, int $limit = 15, ?int $cursor = null): array
    {
        $limit = min($limit, $this->maxPosts);

        // Try to get from cache first
        $cacheKey = $this->getFeedCacheKey($user->id, $limit, $cursor);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $limit, $cursor) {
            return $this->buildPersonalizedFeed($user, $limit, $cursor);
        });
    }

    /**
     * Build personalized feed by querying posts from followed users.
     *
     * @param User $user
     * @param int $limit
     * @param int|null $cursor
     * @return array
     */
    protected function buildPersonalizedFeed(User $user, int $limit, ?int $cursor): array
    {
        $startTime = microtime(true);

        // Get IDs of users that this user follows
        $followingIds = $user->following()->pluck('users.id')->toArray();

        if (empty($followingIds)) {
            Log::info('User has no followed users, returning empty feed', [
                'user_id' => $user->id,
            ]);

            return [
                'posts' => [],
                'next_cursor' => null,
                'has_more' => false,
            ];
        }

        // Build query for posts from followed users
        $query = Post::with(['user:id,name,email'])
            ->withCount(['likes', 'comments'])
            ->whereIn('user_id', $followingIds)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc'); // Secondary sort for consistency

        // Apply cursor pagination
        if ($cursor) {
            $cursorPost = Post::find($cursor);
            if ($cursorPost) {
                $query->where(function (Builder $q) use ($cursorPost) {
                    $q->where('created_at', '<', $cursorPost->created_at)
                        ->orWhere(function (Builder $q2) use ($cursorPost) {
                            $q2->where('created_at', '=', $cursorPost->created_at)
                                ->where('id', '<', $cursorPost->id);
                        });
                });
            }
        }

        // Fetch posts (limit + 1 to check if there are more)
        $posts = $query->limit($limit + 1)->get();

        // Check if there are more posts
        $hasMore = $posts->count() > $limit;
        if ($hasMore) {
            $posts = $posts->take($limit);
        }

        // Add is_liked flag for authenticated user
        $postsData = $posts->map(function ($post) use ($user) {
            $data = $post->toArray();
            $data['is_liked'] = $post->isLikedBy($user->id);
            return $data;
        })->toArray();

        // Get next cursor (last post ID)
        $nextCursor = $hasMore && $posts->isNotEmpty() ? $posts->last()->id : null;

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Personalized feed built', [
            'user_id' => $user->id,
            'following_count' => count($followingIds),
            'posts_fetched' => count($postsData),
            'has_more' => $hasMore,
            'duration_ms' => $duration,
        ]);

        return [
            'posts' => $postsData,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Get trending/public feed (for users not following anyone or public view).
     * Shows all posts ordered by engagement.
     *
     * @param int $limit
     * @param int|null $cursor
     * @param User|null $user
     * @return array
     */
    public function getPublicFeed(int $limit = 15, ?int $cursor = null, ?User $user = null): array
    {
        $limit = min($limit, $this->maxPosts);

        // Cache key includes user_id if authenticated (for is_liked flag)
        $cacheKey = $this->getPublicFeedCacheKey($limit, $cursor, $user?->id);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit, $cursor, $user) {
            return $this->buildPublicFeed($limit, $cursor, $user);
        });
    }

    /**
     * Build public feed ordered by engagement score.
     *
     * @param int $limit
     * @param int|null $cursor
     * @param User|null $user
     * @return array
     */
    protected function buildPublicFeed(int $limit, ?int $cursor, ?User $user): array
    {
        $startTime = microtime(true);

        // Build query with engagement scoring
        $query = Post::with(['user:id,name,email'])
            ->withCount(['likes', 'comments'])
            ->select([
                'posts.*',
                DB::raw('(likes_count * 2 + comments_count * 3) as engagement_score'),
            ])
            ->orderBy('engagement_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        // Apply cursor pagination
        if ($cursor) {
            $cursorPost = Post::withCount(['likes', 'comments'])->find($cursor);
            if ($cursorPost) {
                $cursorScore = ($cursorPost->likes_count * 2 + $cursorPost->comments_count * 3);

                $query->where(function (Builder $q) use ($cursorPost, $cursorScore) {
                    $q->whereRaw('(likes_count * 2 + comments_count * 3) < ?', [$cursorScore])
                        ->orWhere(function (Builder $q2) use ($cursorPost, $cursorScore) {
                            $q2->whereRaw('(likes_count * 2 + comments_count * 3) = ?', [$cursorScore])
                                ->where('created_at', '<', $cursorPost->created_at)
                                ->orWhere(function (Builder $q3) use ($cursorPost, $cursorScore) {
                                    $q3->whereRaw('(likes_count * 2 + comments_count * 3) = ?', [$cursorScore])
                                        ->where('created_at', '=', $cursorPost->created_at)
                                        ->where('id', '<', $cursorPost->id);
                                });
                        });
                });
            }
        }

        // Fetch posts (limit + 1 to check if there are more)
        $posts = $query->limit($limit + 1)->get();

        // Check if there are more posts
        $hasMore = $posts->count() > $limit;
        if ($hasMore) {
            $posts = $posts->take($limit);
        }

        // Add is_liked flag if user is authenticated
        $postsData = $posts->map(function ($post) use ($user) {
            $data = $post->toArray();
            if ($user) {
                $data['is_liked'] = $post->isLikedBy($user->id);
            }
            return $data;
        })->toArray();

        // Get next cursor (last post ID)
        $nextCursor = $hasMore && $posts->isNotEmpty() ? $posts->last()->id : null;

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Public feed built', [
            'posts_fetched' => count($postsData),
            'has_more' => $hasMore,
            'duration_ms' => $duration,
        ]);

        return [
            'posts' => $postsData,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Invalidate feed cache for a user.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUserFeedCache(int $userId): void
    {
        // Pattern to match all feed cache keys for this user
        $pattern = "feed:user:{$userId}:*";

        try {
            // Delete cache keys matching pattern
            Cache::tags(['feed', "user:{$userId}"])->flush();

            Log::info('Feed cache invalidated', [
                'user_id' => $userId,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate feed cache', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate feed cache for all followers of a user (when they create a new post).
     *
     * @param int $userId
     * @return void
     */
    public function invalidateFollowerFeeds(int $userId): void
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return;
            }

            // Get all followers
            $followerIds = $user->followers()->pluck('users.id')->toArray();

            // Invalidate each follower's feed cache
            foreach ($followerIds as $followerId) {
                $this->invalidateUserFeedCache($followerId);
            }

            // Also invalidate public feed cache
            Cache::tags(['feed', 'public'])->flush();

            Log::info('Follower feeds invalidated', [
                'user_id' => $userId,
                'followers_count' => count($followerIds),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate follower feeds', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate cache key for personalized feed.
     *
     * @param int $userId
     * @param int $limit
     * @param int|null $cursor
     * @return string
     */
    protected function getFeedCacheKey(int $userId, int $limit, ?int $cursor): string
    {
        $cursorPart = $cursor ? ":{$cursor}" : '';
        return "feed:user:{$userId}:limit:{$limit}{$cursorPart}";
    }

    /**
     * Generate cache key for public feed.
     *
     * @param int $limit
     * @param int|null $cursor
     * @param int|null $userId
     * @return string
     */
    protected function getPublicFeedCacheKey(int $limit, ?int $cursor, ?int $userId): string
    {
        $cursorPart = $cursor ? ":{$cursor}" : '';
        $userPart = $userId ? ":user:{$userId}" : '';
        return "feed:public:limit:{$limit}{$cursorPart}{$userPart}";
    }

    /**
     * Get feed statistics for a user.
     *
     * @param User $user
     * @return array
     */
    public function getFeedStats(User $user): array
    {
        $followingCount = $user->followingCount();
        $followersCount = $user->followersCount();

        // Count posts in user's feed
        $followingIds = $user->following()->pluck('users.id')->toArray();
        $feedPostsCount = empty($followingIds) ? 0 : Post::whereIn('user_id', $followingIds)->count();

        return [
            'following_count' => $followingCount,
            'followers_count' => $followersCount,
            'feed_posts_available' => $feedPostsCount,
        ];
    }
}
