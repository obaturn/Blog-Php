<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feed Configuration
    |--------------------------------------------------------------------------
    |
    | Configure feed generation settings for the social blog platform.
    | This includes caching, pagination, and feed algorithms.
    |
    */

    /**
     * Cache TTL (Time To Live) for feed results in seconds.
     * Default: 300 seconds (5 minutes)
     */
    'cache_ttl' => env('FEED_CACHE_TTL', 300),

    /**
     * Maximum number of posts that can be fetched in a single request.
     */
    'max_posts' => env('FEED_MAX_POSTS', 50),

    /**
     * Default number of posts per page.
     */
    'default_limit' => env('FEED_DEFAULT_LIMIT', 15),

    /**
     * Engagement scoring weights for public feed.
     */
    'engagement_weights' => [
        'like' => env('FEED_LIKE_WEIGHT', 2),
        'comment' => env('FEED_COMMENT_WEIGHT', 3),
    ],

    /**
     * Enable/disable feed caching.
     */
    'cache_enabled' => env('FEED_CACHE_ENABLED', true),

    /**
     * Feed strategy.
     * Options: 'fan_out_on_read', 'fan_out_on_write'
     * Current implementation: fan_out_on_read
     */
    'strategy' => env('FEED_STRATEGY', 'fan_out_on_read'),
];
