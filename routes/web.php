<?php

use Illuminate\Support\Facades\Route;

/**
 * Web routes are primarily for API documentation or admin panel.
 * Main API routes are in routes/api.php
 */

Route::get('/', function () {
    return response()->json([
        'message' => 'SocialBlog API',
        'version' => '1.0.0',
        'documentation' => url('/api/documentation'),
        'health_check' => url('/api/health'),
    ]);
});