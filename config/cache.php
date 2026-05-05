<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores'  => [
        'file'  => ['driver' => 'file', 'path' => storage_path('framework/cache/data'), 'lock_path' => storage_path('framework/cache/data')],
        'redis' => ['driver' => 'redis', 'connection' => 'cache', 'lock_connection' => 'default'],
        'array' => ['driver' => 'array', 'serialize' => false],
        'null'  => ['driver' => 'null'],
    ],
    'prefix' => env('CACHE_PREFIX', \Illuminate\Support\Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
];
