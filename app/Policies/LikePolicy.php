<?php

namespace App\Policies;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikePolicy
{
    /**
     * Determine whether the user can view likes for a post.
     */
    public function viewAny(?User $user, Post $post): bool
    {
        return true; // Public access
    }

    /**
     * Determine whether the user can like a post.
     */
    public function like(User $user, Post $post): bool
    {
        // User cannot like their own post
        return $user->id !== $post->user_id;
    }

    /**
     * Determine whether the user can unlike a post.
     */
    public function unlike(User $user, Post $post): bool
    {
        return true;
    }

    /**
     * Determine whether the user has already liked a post.
     */
    public function check(User $user, Post $post): bool
    {
        return true;
    }
}