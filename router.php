<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Jalankan script dinamis meskipun file tidak ada secara fisik (supaya script di-handle index.php)
$scriptRoutes = [
    '/preview.php',
    '/upload.php',
    '/update.php',
    '/create.php',
    '/delete.php'
];

if (in_array($path, $scriptRoutes)) {
    require __DIR__ . '/index.php';
    return true;
}

// Kalau file statik ada (misal .css, .js, .png), serve langsung
$file = __DIR__ . $path;
if (file_exists($file) && is_file($file)) {
    return false;
}

// Default fallback ke index
require __DIR__ . '/index.php';
