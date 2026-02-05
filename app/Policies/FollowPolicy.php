<?php

namespace App\Policies;

use App\Models\User;

class FollowPolicy
{
    /**
     * Determine if the user can follow another user.
     */
    public function follow(User $user, User $targetUser): bool
    {
        // Cannot follow yourself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Cannot follow if already following
        if ($user->isFollowing($targetUser)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can unfollow another user.
     */
    public function unfollow(User $user, User $targetUser): bool
    {
        // Cannot unfollow yourself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Can only unfollow if currently following
        return $user->isFollowing($targetUser);
    }
}
