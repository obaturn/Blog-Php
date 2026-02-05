<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FollowController extends Controller
{
    /**
     * Follow a user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function follow(Request $request, User $user): JsonResponse
    {
        try {
            // Check if trying to follow self
            if ($request->user()->id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot follow yourself',
                ], 400);
            }

            // Check if already following
            if ($request->user()->isFollowing($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already following this user',
                ], 400);
            }

            $request->user()->follow($user);

            return response()->json([
                'success' => true,
                'message' => 'Successfully followed user',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'is_following' => true,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to follow user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Unfollow a user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function unfollow(Request $request, User $user): JsonResponse
    {
        try {
            // Check if trying to unfollow self
            if ($request->user()->id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid operation',
                ], 400);
            }

            // Check if not following
            if (!$request->user()->isFollowing($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not following this user',
                ], 400);
            }

            $request->user()->unfollow($user);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unfollowed user',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'is_following' => false,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unfollow user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get list of users that a specific user is following.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function following(Request $request, User $user): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $perPage = min($perPage, 50);

            $following = $user->following()
                ->select('users.id', 'users.name', 'users.email', 'users.created_at')
                ->paginate($perPage);

            // Add is_following flag if authenticated
            $followingData = $following->items();
            if ($request->user()) {
                $followingData = array_map(function ($followedUser) use ($request) {
                    $followedUser->is_following = $request->user()->isFollowing($followedUser);
                    return $followedUser;
                }, $followingData);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'following' => $followingData,
                    'pagination' => [
                        'current_page' => $following->currentPage(),
                        'per_page' => $following->perPage(),
                        'total' => $following->total(),
                        'last_page' => $following->lastPage(),
                        'has_more' => $following->hasMorePages(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch following list',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get list of users following a specific user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function followers(Request $request, User $user): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $perPage = min($perPage, 50);

            $followers = $user->followers()
                ->select('users.id', 'users.name', 'users.email', 'users.created_at')
                ->paginate($perPage);

            // Add is_following flag if authenticated
            $followersData = $followers->items();
            if ($request->user()) {
                $followersData = array_map(function ($follower) use ($request) {
                    $follower->is_following = $request->user()->isFollowing($follower);
                    return $follower;
                }, $followersData);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'followers' => $followersData,
                    'pagination' => [
                        'current_page' => $followers->currentPage(),
                        'per_page' => $followers->perPage(),
                        'total' => $followers->total(),
                        'last_page' => $followers->lastPage(),
                        'has_more' => $followers->hasMorePages(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch followers list',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get authenticated user's followers count and following count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'followers_count' => $request->user()->followersCount(),
                    'following_count' => $request->user()->followingCount(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stats',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get user profile with follow stats.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function userProfile(Request $request, User $user): JsonResponse
    {
        try {
            $profile = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'followers_count' => $user->followersCount(),
                'following_count' => $user->followingCount(),
                'posts_count' => $user->posts()->count(),
            ];

            // Add is_following flag if authenticated
            if ($request->user() && $request->user()->id !== $user->id) {
                $profile['is_following'] = $request->user()->isFollowing($user);
                $profile['is_followed_by'] = $request->user()->isFollowedBy($user);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $profile,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
