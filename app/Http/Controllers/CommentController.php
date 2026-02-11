<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Get comments for a post.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function index(Request $request, Post $post): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $perPage = min($perPage, 50);

            $comments = Comment::where('post_id', $post->id)
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $commentsData = $comments->map(function ($comment) use ($request) {
                $data = [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'email' => $comment->user->email,
                    ],
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];

                // Add can_edit flag if authenticated
                if ($request->user()) {
                    $data['can_edit'] = $request->user()->id === $comment->user_id;
                    $data['can_delete'] = $request->user()->id === $comment->user_id;
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'post_id' => $post->id,
                    'comments' => $commentsData,
                    'pagination' => [
                        'current_page' => $comments->currentPage(),
                        'per_page' => $comments->perPage(),
                        'total' => $comments->total(),
                        'last_page' => $comments->lastPage(),
                        'has_more' => $comments->hasMorePages(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Store a new comment.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'body' => ['required', 'string', 'max:2000'],
            ], [
                'body.required' => 'Comment text is required.',
                'body.max' => 'Comment cannot exceed 2000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
                'body' => $request->body,
            ]);

            $comment->load('user:id,name,email');

            Log::info('Comment created', [
                'comment_id' => $comment->id,
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    'comment' => [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'email' => $comment->user->email,
                        ],
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                    ],
                    'comments_count' => $post->commentsCount(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create comment', [
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Update a comment.
     *
     * @param Request $request
     * @param Comment $comment
     * @return JsonResponse
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        try {
            // Check authorization
            if ($request->user()->id !== $comment->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'You can only edit your own comments',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'body' => ['required', 'string', 'max:2000'],
            ], [
                'body.required' => 'Comment text is required.',
                'body.max' => 'Comment cannot exceed 2000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $comment->update([
                'body' => $request->body,
            ]);

            $comment->load('user:id,name,email');

            Log::info('Comment updated', [
                'comment_id' => $comment->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => [
                    'comment' => [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'email' => $comment->user->email,
                        ],
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Delete a comment (soft delete).
     *
     * @param Request $request
     * @param Comment $comment
     * @return JsonResponse
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        try {
            // Check authorization
            if ($request->user()->id !== $comment->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'You can only delete your own comments',
                ], 403);
            }

            $postId = $comment->post_id;
            $comment->delete(); // Soft delete

            Log::info('Comment deleted', [
                'comment_id' => $comment->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
                'data' => [
                    'post_id' => $postId,
                    'comments_count' => Post::find($postId)->commentsCount(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
