<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$baseDir = __DIR__;
$view = '404';
$title = 'Not Found';

// Direct script access for utilities
$scriptRoutes = [
    '/preview.php' => 'preview.php',
    '/update.php' => 'update.php',
    '/upload.php' => 'upload.php',
    '/create.php' => 'create.php',
    '/delete.php' => 'delete.php'

];

if (array_key_exists($uri, $scriptRoutes)) {
    include __DIR__ . '/' . $scriptRoutes[$uri];
    exit;
}

// Main UI pages
if ($uri === '/' || $uri === '/index.php') {
    include __DIR__ . '/views/main.php';
    exit;
} elseif ($uri === '/files' || str_starts_with($uri, '/files/')) {
    $view = 'files';
    $title = 'File Browser';
} elseif ($uri === '/settings') {
    $view = 'settings';
    $title = 'Settings';
}

include __DIR__ . '/views/layout.php';
