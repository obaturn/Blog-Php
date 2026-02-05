<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure media storage settings for Cloudinary integration.
    | This is used for uploading and managing post images.
    |
    */

    'driver' => env('MEDIA_DRIVER', 'cloudinary'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'secure' => env('CLOUDINARY_SECURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */

    'upload' => [
        // Maximum file size in kilobytes (5MB default)
        'max_size' => env('MEDIA_MAX_SIZE', 5120),

        // Allowed image MIME types
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/jpg',
            'image/gif',
            'image/webp',
        ],

        // Allowed file extensions
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],

        // Image transformations
        'transformations' => [
            'thumbnail' => [
                'width' => 300,
                'height' => 300,
                'crop' => 'fill',
                'quality' => 'auto',
            ],
            'medium' => [
                'width' => 800,
                'height' => 800,
                'crop' => 'limit',
                'quality' => 'auto',
            ],
            'large' => [
                'width' => 1200,
                'height' => 1200,
                'crop' => 'limit',
                'quality' => 'auto',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Folder Structure
    |--------------------------------------------------------------------------
    */

    'folders' => [
        'posts' => 'socialblog/posts',
        'profiles' => 'socialblog/profiles',
    ],
];
