<?php

// The directory where your project is located.
$root = __DIR__;

// Get the requested URI.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Construct the full path to the requested file.
$file = $root . $uri;

// 1. Check if the requested path points to an existing file.
// This is the crucial part for serving your Bootstrap assets (CSS, JS).
if (is_file($file)) {
    // If it's a file, return false.
    // This tells the PHP built-in server to serve the file directly.
    return false;
}

// 2. If it's not a file, it's a virtual route.
// Pass the request to your main index.php to handle the application logic.
require_once $root . '/index.php';

?>