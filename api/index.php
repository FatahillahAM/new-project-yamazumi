<?php

// Buat direktori writable di /tmp untuk Laravel runtime
foreach ([
    '/tmp/storage/logs',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
] as $dir) {
    is_dir($dir) || mkdir($dir, 0755, true);
}

// Override env vars sebelum Laravel boot
foreach ([
    'LOG_CHANNEL'         => 'stderr',
    'CACHE_STORE'         => 'array',
    'SESSION_DRIVER'      => 'cookie',
    'VIEW_COMPILED_PATH'  => '/tmp/storage/framework/views',
] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key]    = $value;
    $_SERVER[$key] = $value;
}

require __DIR__ . '/../public/index.php';