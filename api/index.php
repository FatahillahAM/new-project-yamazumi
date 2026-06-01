<?php

// Debug: cek apakah file bisa diakses
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Buat direktori storage yang dibutuhkan Laravel di /tmp
$dirs = [
    '/tmp/storage/logs',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/app/public',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Override storage path ke /tmp
$_SERVER['APP_STORAGE'] = '/tmp';

// Load Laravel
require __DIR__ . '/../public/index.php';