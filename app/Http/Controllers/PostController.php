<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Jobs\ProcessPostImageJob;
use App\Models\Post;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            // Create post without image first
            $post = Post::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'content' => $request->content,
                'image_url' => null,
            ]);

            // Handle image upload asynchronously if provided
            if ($request->hasFile('image')) {
                $this->handleImageUpload($request->file('image'), $post);
            }

            // Load user relationship
            $post->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully' . ($request->hasFile('image') ? '. Image is being processed.' : ''),
                'data' => [
                    'post' => $post,
                    'image_processing' => $request->hasFile('image'),
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
     * Handle image upload asynchronously.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param Post $post
     * @return void
     */
    protected function handleImageUpload($image, Post $post): void
    {
        try {
            // Store file temporarily
            $tempPath = $image->store('temp/post-images');

            // Dispatch job for async processing
            \App\Jobs\ProcessPostImageJob::dispatch($post, $tempPath);

            \Illuminate\Support\Facades\Log::info('Image upload job dispatched', [
                'post_id' => $post->id,
                'temp_path' => $tempPath,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to dispatch image upload job', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
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

            // Update text fields
            $post->update($request->only(['title', 'content']));

            // Handle new image upload if provided
            if ($request->hasFile('image')) {
                // Delete old image from Cloudinary if exists
                if ($post->image_url) {
                    $mediaService = app(MediaUploadService::class);
                    $publicId = $mediaService->extractPublicId($post->image_url);
                    if ($publicId) {
                        $mediaService->deleteImage($publicId);
                    }
                }

                // Upload new image asynchronously
                $this->handleImageUpload($request->file('image'), $post);
            }

            $post->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully' . ($request->hasFile('image') ? '. New image is being processed.' : ''),
                'data' => [
                    'post' => $post,
                    'image_processing' => $request->hasFile('image'),
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

            // Delete image from Cloudinary if exists
            if ($post->image_url) {
                $mediaService = app(MediaUploadService::class);
                $publicId = $mediaService->extractPublicId($post->image_url);
                if ($publicId) {
                    $mediaService->deleteImage($publicId);
                }
            }

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
