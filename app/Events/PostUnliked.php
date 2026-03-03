<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostUnlikedEvent - Fired when a user unlikes a post
 *
 * Use this event to:
 * - Update activity feeds
 * - Track analytics
 * - Update real-time counts
 */
class PostUnliked
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
     * Get the post that was unliked.
     */
    public function getPost(): Post
    {
        return $this->post;
    }

    /**
     * Get the user who unliked the post.
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
