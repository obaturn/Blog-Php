<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine if the user can view any posts.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view posts (public)
        return true;
    }

    /**
     * Determine if the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Anyone can view a single post (public)
        return true;
    }

    /**
     * Determine if the user can create posts.
     */
    public function create(User $user): bool
    {
        // Only authenticated users can create posts
        return true;
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Only the post owner can update
        return $user->id === $post->user_id;
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Only the post owner can delete
        return $user->id === $post->user_id;
    }

    /**
     * Determine if the user can restore the post.
     */
    public function restore(User $user, Post $post): bool
    {
        // Only the post owner can restore
        return $user->id === $post->user_id;
    }

    /**
     * Determine if the user can permanently delete the post.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        // Only the post owner can force delete
        return $user->id === $post->user_id;
    }
}
