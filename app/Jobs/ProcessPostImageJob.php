<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\MediaUploadService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * ProcessPostImageJob - Handles post image upload to Cloudinary
 *
 * Features:
 * - Configurable retry attempts
 * - Exponential backoff between retries
 * - Unique job locking to prevent duplicates
 * - Detailed logging for debugging
 * - Graceful fallback on failure
 */
class ProcessPostImageJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Seconds to wait before retrying (exponential backoff).
     */
    public int $maxBackoff = 60;

    /**
     * Unique job lock time in seconds.
     */
    public int $uniqueFor = 3600;

    /**
     * Priority of the job (lower = higher priority).
     */
    public int $priority = 10;

    protected Post $post;
    protected string $tempFilePath;
    protected int $userId;

    /**
     * Create a new job instance.
     *
     * @param Post $post
     * @param string $tempFilePath Path to temporary file
     */
    public function __construct(Post $post, string $tempFilePath)
    {
        $this->post = $post;
        $this->tempFilePath = $tempFilePath;
        $this->userId = $post->user_id;
        $this->onQueue('media');
        $this->setConnection('database');
    }

    /**
     * Get the unique ID for this job (prevents duplicate processing).
     */
    public function uniqueId(): string
    {
        return "post_image_{$this->post->id}";
    }

    /**
     * Calculate backoff delay in seconds (exponential with jitter).
     */
    public function backoff(): array
    {
        $attempt = $this->attempts();
        
        // Exponential backoff: 10s, 30s, 60s
        $delays = [10, 30, 60];
        
        if ($attempt <= count($delays)) {
            return [$delays[$attempt - 1]];
        }
        
        // Cap at max backoff
        return [$this->maxBackoff];
    }

    /**
     * Execute the job.
     */
    public function handle(MediaUploadService $mediaService): void
    {
        // Check if job should still run
        if (!$this->shouldRun()) {
            Log::info('Skipping job - post no longer exists', [
                'post_id' => $this->post->id,
            ]);
            return;
        }

        Log::info('Processing post image', [
            'post_id' => $this->post->id,
            'temp_file' => $this->tempFilePath,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Verify temp file exists
            if (!$this->validateTempFile()) {
                $this->fail(new \RuntimeException('Temporary file not found or invalid'));
                return;
            }

            // Get full path
            $fullPath = Storage::path($this->tempFilePath);

            // Verify file is readable
            if (!is_readable($fullPath)) {
                throw new \RuntimeException('Temporary file is not readable');
            }

            // Create UploadedFile instance from temp file
            $uploadedFile = new UploadedFile(
                $fullPath,
                basename($this->tempFilePath),
                mime_content_type($fullPath),
                null,
                true
            );

            // Quick validation before upload
            $validationResult = $mediaService->validateFile($uploadedFile);
            if ($validationResult !== true) {
                Log::warning('File validation failed before upload', [
                    'post_id' => $this->post->id,
                    'error' => $validationResult,
                ]);
                throw new \RuntimeException("File validation failed: {$validationResult}");
            }

            // Upload to Cloudinary
            $result = $mediaService->uploadImage($uploadedFile, 'posts');

            if (!$result['success']) {
                throw new \RuntimeException($result['error'] ?? 'Upload failed');
            }

            // Update post with image URL using atomic update
            $this->updatePostWithImage($result['data']);

            // Clean up temporary file
            $this->cleanupTempFile();

            Log::info('Post image processed successfully', [
                'post_id' => $this->post->id,
                'image_url' => $result['data']['url'],
                'public_id' => $result['data']['public_id'],
                'attempts' => $this->attempts(),
            ]);

        } catch (Throwable $e) {
            $this->handleFailure($e, $mediaService);
        }
    }

    /**
     * Validate that the temporary file exists and is valid.
     */
    protected function validateTempFile(): bool
    {
        if (!Storage::exists($this->tempFilePath)) {
            Log::warning('Temp file does not exist', [
                'post_id' => $this->post->id,
                'temp_file' => $this->tempFilePath,
            ]);
            return false;
        }

        $fullPath = Storage::path($this->tempFilePath);
        $fileSize = filesize($fullPath);

        if ($fileSize === 0) {
            Log::warning('Temp file is empty', [
                'post_id' => $this->post->id,
                'temp_file' => $this->tempFilePath,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Update post with image URL (atomic operation).
     */
    protected function updatePostWithImage(array $imageData): void
    {
        $updated = $this->post->update([
            'image_url' => $imageData['url'],
        ]);

        if (!$updated) {
            throw new \RuntimeException('Failed to update post with image URL');
        }

        // Refresh the model
        $this->post->refresh();
    }

    /**
     * Handle job failure with logging and cleanup.
     */
    protected function handleFailure(Throwable $e, MediaUploadService $mediaService): void
    {
        $attempt = $this->attempts();
        $maxTries = $this->tries;

        Log::error('Failed to process post image', [
            'post_id' => $this->post->id,
            'error' => $e->getMessage(),
            'attempt' => $attempt,
            'max_tries' => $maxTries,
            'trace' => $e->getTraceAsString(),
        ]);

        // Check if we should retry
        if ($attempt >= $maxTries) {
            // Final failure - mark post accordingly
            $this->handleFinalFailure($e);
        } else {
            // Let Laravel retry the job
            throw $e;
        }
    }

    /**
     * Handle permanent job failure.
     */
    protected function handleFinalFailure(Throwable $e): void
    {
        try {
            // Update post to indicate upload failure
            $this->post->update([
                'image_upload_failed' => true,
                'image_upload_error' => $e->getMessage(),
            ]);

            // Clean up temp file
            $this->cleanupTempFile();

            Log::error('Post image processing failed permanently', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);

            // TODO: Send notification to user about failed upload
            // Notification::route('mail', $this->post->user->email)
            //     ->notify(new PostImageUploadFailed($this->post, $e->getMessage()));

        } catch (\Exception $innerE) {
            Log::error('Failed to handle final failure', [
                'post_id' => $this->post->id,
                'error' => $innerE->getMessage(),
            ]);
        }
    }

    /**
     * Clean up temporary file.
     */
    protected function cleanupTempFile(): void
    {
        try {
            if (!empty($this->tempFilePath) && Storage::exists($this->tempFilePath)) {
                Storage::delete($this->tempFilePath);
                Log::debug('Cleaned up temp file', ['temp_file' => $this->tempFilePath]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup temp file', [
                'temp_file' => $this->tempFilePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if the job should still run.
     */
    protected function shouldRun(): bool
    {
        // Check if post still exists
        return $this->post->exists;
    }

    /**
     * Determine if the job should be released back to the queue.
     */
    public function shouldQueue(Throwable $e): bool
    {
        // Retry on most errors except validation errors
        if ($e instanceof \InvalidArgumentException) {
            return false;
        }

        return $this->attempts() < $this->tries;
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Post image processing job failed permanently', [
            'post_id' => $this->post->id,
            'user_id' => $this->userId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        // You can send alerts here, e.g., to Slack, email, etc.
        // event(new ImageProcessingFailed($this->post, $exception));
    }

    /**
     * Get tags for the job (for monitoring).
     */
    public function tags(): array
    {
        return ['media', "post:{$this->post->id}", "user:{$this->userId}"];
    }
}
