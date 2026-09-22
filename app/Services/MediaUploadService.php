<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MediaUploadService - Handles image uploads to Cloudinary
 *
 * Features:
 * - File validation (size, type, dimensions)
 * - Cloudinary upload with automatic optimization
 * - Error handling and logging
 * - Fallback to local storage if Cloudinary unavailable
 */
class MediaUploadService
{
    protected ?Cloudinary $cloudinary = null;
    protected bool $isConfigured = false;

    /**
     * Maximum image dimensions (width x height)
     */
    protected const MAX_WIDTH = 4096;
    protected const MAX_HEIGHT = 4096;

    public function __construct()
    {
        $this->initializeCloudinary();
    }

    /**
     * Initialize Cloudinary connection with error handling.
     */
    protected function initializeCloudinary(): void
    {
        $cloudName = config('media.cloudinary.cloud_name');
        $apiKey = config('media.cloudinary.api_key');
        $apiSecret = config('media.cloudinary.api_secret');

        if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
            Log::warning('Cloudinary not properly configured. Check media.cloudinary settings in config/media.php and .env file.');
            $this->isConfigured = false;
            return;
        }

        try {
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                    'secure' => config('media.cloudinary.secure', true),
                ],
            ]);
            $this->isConfigured = true;

        } catch (\Exception $e) {
            Log::error('Failed to initialize Cloudinary', [
                'error' => $e->getMessage(),
                'cloud_name' => $cloudName,
            ]);
            $this->isConfigured = false;
        }
    }

    /**
     * Check if Cloudinary is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured && $this->cloudinary !== null;
    }

    /**
     * Upload an image to Cloudinary.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function uploadImage(UploadedFile $file, string $folder = 'posts', array $options = []): array
    {
        // Check configuration
        if (!$this->isConfigured()) {
            return $this->errorResponse('Cloudinary is not properly configured. Please check your API credentials.');
        }

        // Validate file
        $validationResult = $this->validateFile($file);
        if ($validationResult !== true) {
            return $this->errorResponse($validationResult);
        }

        try {
            // Generate unique public ID
            $publicId = $this->generatePublicId($folder);

            // Merge default options
            $uploadOptions = array_merge([
                'folder' => config("media.folders.{$folder}", "socialblog/{$folder}"),
                'public_id' => $publicId,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ],
            ], $options);

            // Upload to Cloudinary
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $uploadOptions
            );

            Log::info('Image uploaded to Cloudinary', [
                'public_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'folder' => $folder,
            ]);

            return $this->successResponse([
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'format' => $result['format'] ?? null,
                'bytes' => $result['bytes'] ?? null,
            ]);
        } catch (\Cloudinary\Api\UploadApiException $e) {
            Log::error('Cloudinary upload API error', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $file->getClientOriginalName(),
            ]);

            return $this->errorResponse('Failed to upload image: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('An unexpected error occurred during upload.');
        }
    }

    /**
     * Upload image with local fallback.
     * If Cloudinary fails, try local storage.
     */
    public function uploadImageWithFallback(UploadedFile $file, string $folder = 'posts'): array
    {
        // Try Cloudinary first
        $result = $this->uploadImage($file, $folder);

        if ($result['success']) {
            return $result;
        }

        // Fallback to local storage
        Log::warning('Cloudinary upload failed, falling back to local storage', [
            'error' => $result['error'],
        ]);

        return $this->uploadToLocal($file, $folder);
    }

    /**
     * Upload file to local storage as fallback.
     */
    protected function uploadToLocal(UploadedFile $file, string $folder): array
    {
        try {
            $filename = $this->generatePublicId($folder) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folder, $filename, 'public');

            $url = asset('storage/' . $path);

            Log::info('Image uploaded to local storage', [
                'path' => $path,
                'url' => $url,
            ]);

            return $this->successResponse([
                'url' => $url,
                'public_id' => $folder . '/' . $filename,
                'is_local' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Local storage fallback also failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to upload image to any storage backend.');
        }
    }

    /**
     * Delete an image from Cloudinary.
     */
    public function deleteImage(string $publicId): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Cloudinary is not configured');
        }

        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);

            Log::info('Image deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result['result'],
            ]);

            return $this->successResponse(['deleted' => $result['result'] === 'ok']);
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed', [
                'error' => $e->getMessage(),
                'public_id' => $publicId,
            ]);

            return $this->errorResponse('Failed to delete image: ' . $e->getMessage());
        }
    }

    /**
     * Get optimized image URL with transformations.
     */
    public function getTransformedUrl(string $publicId, string $transformation = 'medium'): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $transformConfig = config("media.upload.transformations.{$transformation}");

            if (!$transformConfig) {
                return null;
            }

            return $this->cloudinary->image($publicId)
                ->resize(
                    \Cloudinary\Transformation\Resize::fill()
                        ->width($transformConfig['width'])
                        ->height($transformConfig['height'])
                )
                ->delivery(
                    \Cloudinary\Transformation\Quality::auto()
                )
                ->toUrl();
        } catch (\Exception $e) {
            Log::error('Failed to generate transformed URL', [
                'error' => $e->getMessage(),
                'public_id' => $publicId,
            ]);

            return null;
        }
    }

    /**
     * Validate uploaded file.
     *
     * @param UploadedFile $file
     * @return bool|string Returns true if valid, or error message string
     */
    public function validateFile(UploadedFile $file): bool|string
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return 'File upload failed. Please try again.';
        }

        // Check file size
        $maxSize = (int) config('media.upload.max_size', 5120); // KB
        $fileSize = $file->getSize();
        
        if ($fileSize > $maxSize * 1024) {
            return sprintf(
                'File too large. Maximum size is %dMB. Your file is %s.',
                $maxSize / 1024,
                $this->formatBytes($fileSize)
            );
        }

        // Check MIME type
        $allowedTypes = config('media.upload.allowed_types', []);
        $mimeType = $file->getMimeType();
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return sprintf(
                'Invalid file type: %s. Allowed types: %s',
                $mimeType,
                implode(', ', $allowedTypes)
            );
        }

        // Check extension
        $allowedExtensions = config('media.upload.allowed_extensions', []);
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            return sprintf(
                'Invalid file extension: %s. Allowed extensions: %s',
                $extension,
                implode(', ', $allowedExtensions)
            );
        }

        // Check image dimensions (if possible)
        try {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo !== false) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];

                if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
                    return sprintf(
                        'Image dimensions too large. Maximum: %dx%d pixels. Your image: %dx%d pixels.',
                        self::MAX_WIDTH,
                        self::MAX_HEIGHT,
                        $width,
                        $height
                    );
                }

                // Check minimum dimensions
                if ($width < 10 || $height < 10) {
                    return 'Image dimensions are too small. Minimum: 10x10 pixels.';
                }
            }
        } catch (\Exception $e) {
            // Ignore dimension check errors
            Log::warning('Could not check image dimensions', ['error' => $e->getMessage()]);
        }

        // Check for actual image content (not just extension)
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return 'File does not appear to be a valid image.';
        }

        return true;
    }

    /**
     * Validate file synchronously (for quick checks).
     */
    public function isValidFile(UploadedFile $file): bool
    {
        $result = $this->validateFile($file);
        return $result === true;
    }

    /**
     * Generate unique public ID for Cloudinary.
     */
    protected function generatePublicId(string $folder): string
    {
        return sprintf(
            '%s_%s',
            $folder,
            Str::random(20)
        );
    }

    /**
     * Extract public ID from Cloudinary URL.
     */
    public function extractPublicId(string $url): ?string
    {
        try {
            preg_match('/\/upload\/(?:v\d+\/)?(.+)\.[a-z]+$/i', $url, $matches);
            return $matches[1] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Create success response.
     */
    protected function successResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'error' => null,
        ];
    }

    /**
     * Create error response.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'data' => null,
            'error' => $message,
        ];
    }
}
