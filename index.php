<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$baseDir = __DIR__;
$view = '404';
$title = 'Not Found';

// redirect
$requestUri = $_SERVER['REQUEST_URI'];
if ($requestUri === '/' || $requestUri === '/index.php') {
    header("Location: /files");
    exit;
}


// Routing
if ($uri === '/' || $uri === '/files') {
    $view = 'files';
    $title = 'File Browser';
} elseif (str_starts_with($uri, '/files/')) {
    $view = 'files';
    $title = 'File Browser';
} elseif ($uri === '/settings') {
    $view = 'settings';
    $title = 'Settings';
}

include __DIR__ . '/views/layout.php';
