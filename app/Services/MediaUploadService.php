<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaUploadService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('media.cloudinary.cloud_name'),
                'api_key' => config('media.cloudinary.api_key'),
                'api_secret' => config('media.cloudinary.api_secret'),
                'secure' => config('media.cloudinary.secure'),
            ],
        ]);
    }

    /**
     * Upload an image to Cloudinary.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array|null Returns ['url' => string, 'public_id' => string] or null on failure
     */
    public function uploadImage(UploadedFile $file, string $folder = 'posts', array $options = []): ?array
    {
        try {
            // Validate file
            if (!$this->validateFile($file)) {
                throw new \Exception('Invalid file');
            }

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
            ]);

            return [
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'format' => $result['format'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            return null;
        }
    }

    /**
     * Delete an image from Cloudinary.
     *
     * @param string $publicId
     * @return bool
     */
    public function deleteImage(string $publicId): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);

            Log::info('Image deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result['result'],
            ]);

            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed', [
                'error' => $e->getMessage(),
                'public_id' => $publicId,
            ]);

            return false;
        }
    }

    /**
     * Get optimized image URL with transformations.
     *
     * @param string $publicId
     * @param string $transformation (thumbnail, medium, large)
     * @return string|null
     */
    public function getTransformedUrl(string $publicId, string $transformation = 'medium'): ?string
    {
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
     * @return bool
     */
    protected function validateFile(UploadedFile $file): bool
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return false;
        }

        // Check file size
        $maxSize = config('media.upload.max_size', 5120); // KB
        if ($file->getSize() > $maxSize * 1024) {
            return false;
        }

        // Check MIME type
        $allowedTypes = config('media.upload.allowed_types', []);
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return false;
        }

        // Check extension
        $allowedExtensions = config('media.upload.allowed_extensions', []);
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique public ID for Cloudinary.
     *
     * @param string $folder
     * @return string
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
     *
     * @param string $url
     * @return string|null
     */
    public function extractPublicId(string $url): ?string
    {
        try {
            // Extract public_id from Cloudinary URL
            // Format: https://res.cloudinary.com/{cloud_name}/image/upload/{transformations}/{public_id}.{format}
            preg_match('/\/upload\/(?:v\d+\/)?(.+)\.[a-z]+$/i', $url, $matches);
            return $matches[1] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
