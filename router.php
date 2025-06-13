<?php
// router.php

// The directory where your project is located.
$root = __DIR__;

// Get the requested URI path (e.g., /bootstrap/css/bootstrap.min.css)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Construct the full path to the requested file on the server
$file = $root . $uri;

// This is the most important part:
// Check if the requested path is an actual, existing file.
if (is_file($file)) {
    // If it is a file (like bootstrap.min.css), return false.
    // This tells the PHP server: "Stop running PHP and just serve this file directly."
    return false;
}

// If the request is NOT for a file (e.g., it's for a page like /files or /settings),
// then load your main application router, index.php.
require_once $root . '/index.php';

?>