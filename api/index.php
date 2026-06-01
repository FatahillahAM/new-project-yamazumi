<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Buat direktori writable di /tmp
foreach ([
    '/tmp/storage/logs',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/bootstrap/cache',
] as $dir) {
    is_dir($dir) || mkdir($dir, 0755, true);
}

// Override env sebelum Laravel boot
foreach ([
    'LOG_CHANNEL'  => 'stderr',
    'CACHE_STORE'  => 'array',
    'SESSION_DRIVER' => 'cookie',
] as $key => $val) {
    putenv("$key=$val");
    $_ENV[$key] = $val;
    $_SERVER[$key] = $val;
}

// Load vendor autoloader
require __DIR__ . '/../vendor/autoload.php';

// Buat Application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Set storage path ke /tmp SEBELUM request dihandle
// (useStoragePath ada di Application instance, bukan di ApplicationBuilder)
$app->useStoragePath('/tmp/storage');
$app->instance('path.storage', '/tmp/storage');

// Handle request
$app->handleRequest(Request::capture());