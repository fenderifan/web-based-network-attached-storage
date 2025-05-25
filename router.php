<?php
// router.php

// Serve the requested resource as-is if it exists
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$file = __DIR__ . $path;
if (file_exists($file) && is_file($file)) {
    return false;
}

// Otherwise, fallback to index.php
require_once __DIR__ . '/index.php';
