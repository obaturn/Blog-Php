<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * Display a listing of all posts (public).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $perPage = min($perPage, 50); // Max 50 per page

            $posts = Post::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $posts->items(),
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'last_page' => $posts->lastPage(),
                        'has_more' => $posts->hasMorePages(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch posts',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Store a newly created post.
     *
     * @param StorePostRequest $request
     * @return JsonResponse
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        try {
            $post = Post::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'content' => $request->content,
                'image_url' => null, // Will be handled in Phase 3 with Cloudinary
            ]);

            // Load user relationship
            $post->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => [
                    'post' => $post,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Display the specified post.
     *
     * @param Post $post
     * @return JsonResponse
     */
    public function show(Post $post): JsonResponse
    {
        try {
            $post->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'data' => [
                    'post' => $post,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch post',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Update the specified post.
     *
     * @param UpdatePostRequest $request
     * @param Post $post
     * @return JsonResponse
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        try {
            // Authorization check
            Gate::authorize('update', $post);

            $post->update($request->only(['title', 'content']));
            $post->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => [
                    'post' => $post,
                ],
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => 'You are not authorized to update this post',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Remove the specified post.
     *
     * @param Post $post
     * @return JsonResponse
     */
    public function destroy(Post $post): JsonResponse
    {
        try {
            // Authorization check
            Gate::authorize('delete', $post);

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => 'You are not authorized to delete this post',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get posts by authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myPosts(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $perPage = min($perPage, 50);

            $posts = Post::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $posts->items(),
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'last_page' => $posts->lastPage(),
                        'has_more' => $posts->hasMorePages(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch your posts',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
