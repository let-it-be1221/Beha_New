<?php

return [
    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'redis')),
    'stores' => [
        'array'  => ['driver' => 'array', 'serialize' => false],
        'file'   => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
        'redis'  => [
            'driver'     => 'redis',
            'connection' => 'cache',
        ],
        'database' => [
            'driver'       => 'database',
            'table'        => 'cache',
            'connection'   => null,
            'lock_connection' => null,
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'beha_cache_'),
];
