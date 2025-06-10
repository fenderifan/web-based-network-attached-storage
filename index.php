<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$baseDir = __DIR__;
$view = '404';
$title = 'Not Found';

// Handle POST request for settings before any HTML is rendered
if ($uri === '/settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config.php';
    $settings = load_settings();

    // Update settings from POST data
    $settings['theme'] = $_POST['theme'] ?? 'light';
    $settings['default_sort'] = $_POST['default_sort'] ?? 'name_asc';
    $settings['show_hidden_files'] = isset($_POST['show_hidden_files']);
    $settings['type_grouping'] = isset($_POST['type_grouping']);
    $settings['timezone'] = $_POST['timezone'] ?? 'Asia/Jakarta';

    // Set header to indicate a JSON response
    header('Content-Type: application/json');

    if (save_settings($settings)) {
        // Send a success JSON response
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
    } else {
        // Send an error JSON response
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: Could not write to config.json.']);
    }
    
    exit; // Stop the script here. This is crucial.
}

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
