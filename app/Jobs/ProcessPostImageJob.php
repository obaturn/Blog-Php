<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\MediaUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessPostImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60]; // Exponential backoff in seconds

    protected Post $post;
    protected string $tempFilePath;

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
        $this->onQueue('media'); // Use dedicated media queue
    }

    /**
     * Execute the job.
     *
     * @param MediaUploadService $mediaService
     * @return void
     */
    public function handle(MediaUploadService $mediaService): void
    {
        try {
            Log::info('Processing post image', [
                'post_id' => $this->post->id,
                'temp_file' => $this->tempFilePath,
            ]);

            // Check if temp file exists
            if (!Storage::exists($this->tempFilePath)) {
                throw new \Exception('Temporary file not found');
            }

            // Get full path
            $fullPath = Storage::path($this->tempFilePath);

            // Create UploadedFile instance from temp file
            $uploadedFile = new UploadedFile(
                $fullPath,
                basename($this->tempFilePath),
                mime_content_type($fullPath),
                null,
                true
            );

            // Upload to Cloudinary
            $result = $mediaService->uploadImage($uploadedFile, 'posts');

            if (!$result) {
                throw new \Exception('Failed to upload image to Cloudinary');
            }

            // Update post with image URL
            $this->post->update([
                'image_url' => $result['url'],
            ]);

            Log::info('Post image processed successfully', [
                'post_id' => $this->post->id,
                'image_url' => $result['url'],
            ]);

            // Clean up temporary file
            Storage::delete($this->tempFilePath);
        } catch (\Exception $e) {
            Log::error('Failed to process post image', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Clean up temp file on final failure
            if ($this->attempts() >= $this->tries) {
                Storage::delete($this->tempFilePath);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Post image processing failed permanently', [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);

        // Update post to indicate failure (optional)
        // You could add a 'image_upload_failed' flag to posts table
    }
}
