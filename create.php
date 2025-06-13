<?php
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/config.php';

// Set timezone for consistent logs
$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

$baseDir = __DIR__ . '/files';
$targetFolder = trim($_POST['targetFolder'] ?? '', '/');
$folderName = trim($_POST['folderName'] ?? '', '/\\');

// Basic validation for folder name
if (!$folderName || $folderName === '.' || $folderName === '..') {
    http_response_code(400);
    echo "Invalid folder name.";
    exit;
}

$targetPath = $baseDir . ($targetFolder ? "/$targetFolder" : '');
$newFolderPath = $targetPath . '/' . $folderName;

if (file_exists($newFolderPath)) {
    http_response_code(409); // 409 Conflict is more appropriate
    echo "Folder already exists.";
    exit;
}

if (@mkdir($newFolderPath, 0777, true)) {
    // Log the successful creation
    write_log('Created folder "' . $folderName . '" in ' . ($targetFolder ?: '/'));
    echo "Folder created.";
} else {
    http_response_code(500);
    echo "Failed to create folder. Check permissions.";
}
