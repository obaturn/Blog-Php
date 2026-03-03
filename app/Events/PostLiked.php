<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostLikedEvent - Fired when a user likes a post
 *
 * Use this event to:
 * - Send notifications to post owner
 * - Update activity feeds
 * - Track analytics
 * - Update real-time counts
 */
class PostLiked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Post $post;
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    /**
     * Get the post that was liked.
     */
    public function getPost(): Post
    {
        return $this->post;
    }

    /**
     * Get the user who liked the post.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get the post owner's ID (for notification targeting).
     */
    public function getOwnerId(): int
    {
        return $this->post->user_id;
    }

    /**
     * Check if the liker is also the post owner.
     */
    public function isSelfLike(): bool
    {
        return $this->user->id === $this->post->user_id;
    }
}
