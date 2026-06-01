<?php

// TEST 1: PHP berjalan?
echo "=== DEBUG ===\n";

// TEST 2: vendor ada?
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("GAGAL: vendor/autoload.php tidak ditemukan di: " . $autoload);
}
echo "vendor OK\n";

// TEST 3: public/index.php ada?
$publicIndex = __DIR__ . '/../public/index.php';
if (!file_exists($publicIndex)) {
    die("GAGAL: public/index.php tidak ditemukan");
}
echo "public/index.php OK\n";

// TEST 4: .env ada?
$env = __DIR__ . '/../.env';
echo ".env exists: " . (file_exists($env) ? 'YES' : 'NO (pakai env variables)') . "\n";

// TEST 5: Buat storage dirs
$dirs = [
    '/tmp/storage/logs',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
];
foreach ($dirs as $dir) {
    mkdir($dir, 0755, true);
}
echo "storage dirs OK\n";

// TEST 6: Load Laravel
echo "Loading Laravel...\n";
ob_start();
try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    ob_end_clean();
    die("LARAVEL ERROR: " . $e->getMessage() . "\n" .
        "File: " . $e->getFile() . " line " . $e->getLine());
}