<?php

// Buat direktori writable di /tmp sebelum Laravel boot
$dirs = [
    '/tmp/storage/logs',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/bootstrap/cache',
];
foreach ($dirs as $dir) {
    is_dir($dir) || mkdir($dir, 0755, true);
}

// Override env vars sebelum Laravel baca config
putenv('LOG_CHANNEL=stderr');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=cookie');

require __DIR__ . '/../public/index.php';