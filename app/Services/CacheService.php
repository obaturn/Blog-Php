<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService - Handles caching with automatic fallback
 *
 * Features:
 * - Redis as primary cache (fast, in-memory)
 * - File-based fallback when Redis is unavailable
 * - Graceful degradation
 * - Connection testing before operations
 */
class CacheService
{
    /**
     * Default cache TTL in seconds.
     */
    public const DEFAULT_TTL = 300; // 5 minutes

    /**
     * Fallback driver to use when primary fails.
     */
    protected const FALLBACK_DRIVER = 'file';

    /**
     * Cache store currently in use.
     */
    protected string $activeDriver;

    /**
     * Create a new cache service instance.
     */
    public function __construct()
    {
        $this->activeDriver = config('cache.default', 'file');
    }

    /**
     * Get a value from cache.
     *
     * @param string $key
     * @param mixed|null $default
     * @param int|null $ttl TTL override in seconds
     * @return mixed
     */
    public function get(string $key, mixed $default = null, ?int $ttl = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed, falling back', [
                'key' => $key,
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackGet($key, $default);
        }
    }

    /**
     * Set a value in cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl TTL in seconds
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? config('cache.ttl', self::DEFAULT_TTL);

        try {
            return Cache::set($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache set failed, falling back', [
                'key' => $key,
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackSet($key, $value, $ttl);
        }
    }

    /**
     * Remember (get or set) a value in cache.
     *
     * @param string $key
     * @param int|null $ttl
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed, falling back', [
                'key' => $key,
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            // Try to get from fallback first
            $fallbackValue = $this->fallbackGet($key);
            if ($fallbackValue !== null) {
                return $fallbackValue;
            }

            // Execute callback and store in fallback
            $value = $callback();
            $this->fallbackSet($key, $value, $ttl ?? self::DEFAULT_TTL);

            return $value;
        }
    }

    /**
     * Delete a value from cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed, falling back', [
                'key' => $key,
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackForget($key);
        }
    }

    /**
     * Check if a key exists in cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            Log::warning('Cache has failed, falling back', [
                'key' => $key,
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackHas($key);
        }
    }

    /**
     * Flush all cache (use with caution).
     *
     * @return bool
     */
    public function flush(): bool
    {
        try {
            Cache::flush();
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache flush failed', [
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test the cache connection.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        $testKey = '__cache_test_' . time();
        $testValue = 'test_value_' . uniqid();

        try {
            Cache::set($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            return $retrieved === $testValue;
        } catch (\Exception $e) {
            Log::error('Cache connection test failed', [
                'driver' => $this->activeDriver,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the current active driver.
     */
    public function getActiveDriver(): string
    {
        return $this->activeDriver;
    }

    /**
     * Switch to fallback driver temporarily.
     */
    public function useFallback(): void
    {
        $this->activeDriver = self::FALLBACK_DRIVER;
        Config::set('cache.default', self::FALLBACK_DRIVER);
    }

    // ========== Fallback Methods ==========

    /**
     * Get from file-based fallback.
     */
    protected function fallbackGet(string $key, mixed $default = null): mixed
    {
        try {
            $fallbackPath = $this->getFallbackPath($key);
            if (!file_exists($fallbackPath)) {
                return $default;
            }

            $content = file_get_contents($fallbackPath);
            $data = json_decode($content, true);

            if (!$data || !isset($data['value'], $data['expires'])) {
                return $default;
            }

            if ($data['expires'] < time()) {
                unlink($fallbackPath);
                return $default;
            }

            return $data['value'];
        } catch (\Exception $e) {
            Log::error('Fallback cache get failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    /**
     * Set to file-based fallback.
     */
    protected function fallbackSet(string $key, mixed $value, int $ttl): bool
    {
        try {
            $fallbackPath = $this->getFallbackPath($key);
            $directory = dirname($fallbackPath);

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $data = [
                'value' => $value,
                'expires' => time() + $ttl,
            ];

            return file_put_contents($fallbackPath, json_encode($data)) !== false;
        } catch (\Exception $e) {
            Log::error('Fallback cache set failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete from file-based fallback.
     */
    protected function fallbackForget(string $key): bool
    {
        try {
            $fallbackPath = $this->getFallbackPath($key);

            if (file_exists($fallbackPath)) {
                return unlink($fallbackPath);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Fallback cache forget failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if key exists in file-based fallback.
     */
    protected function fallbackHas(string $key): bool
    {
        try {
            $fallbackPath = $this->getFallbackPath($key);

            if (!file_exists($fallbackPath)) {
                return false;
            }

            $content = file_get_contents($fallbackPath);
            $data = json_decode($content, true);

            if (!$data || !isset($data['expires'])) {
                return false;
            }

            if ($data['expires'] < time()) {
                unlink($fallbackPath);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the file path for fallback cache.
     */
    protected function getFallbackPath(string $key): string
    {
        $hash = hash('sha256', $key);
        $path = storage_path('framework/cache/fallback/' . substr($hash, 0, 2) . '/' . substr($hash, 2));
        return $path;
    }

    /**
     * Clean up expired fallback cache files.
     */
    public function cleanupFallbackCache(): int
    {
        $cleaned = 0;
        $directory = storage_path('framework/cache/fallback');

        if (!is_dir($directory)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                try {
                    $content = file_get_contents($file->getPathname());
                    $data = json_decode($content, true);

                    if ($data && isset($data['expires']) && $data['expires'] < time()) {
                        unlink($file->getPathname());
                        $cleaned++;
                    }
                } catch (\Exception $e) {
                    // Ignore invalid files
                }
            }
        }

        return $cleaned;
    }
}
