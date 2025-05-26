<?php
$baseDir = __DIR__ . '/files';
$targetFolder = trim($_POST['targetFolder'] ?? '', '/');
$folderName = trim($_POST['folderName'] ?? '', '/\\');

if (!$folderName) {
    http_response_code(400);
    echo "Invalid folder name.";
    exit;
}

$targetPath = $baseDir . ($targetFolder ? "/$targetFolder" : '');
$newFolderPath = $targetPath . '/' . $folderName;

if (!file_exists($newFolderPath)) {
    if (!mkdir($newFolderPath, 0777, true)) {
        http_response_code(500);
        echo "Failed to create folder.";
        exit;
    }
    echo "Folder created.";
} else {
    http_response_code(400);
    echo "Folder already exists.";
}
