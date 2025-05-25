<?php
$baseDir = realpath(__DIR__ . '/files');

$oldPath = $_POST['oldPath'] ?? '';
$newName = $_POST['newName'] ?? '';

if (!$oldPath || !$newName) {
    http_response_code(400);
    echo "Missing required fields.";
    exit;
}

// Sanitize and resolve full path
$oldPath = urldecode($oldPath);
$folderPath = dirname($oldPath);
$extension = pathinfo($oldPath, PATHINFO_EXTENSION);

$baseName = pathinfo($oldPath, PATHINFO_FILENAME);

// Ensure file actually exists
if (!file_exists($oldPath)) {
    echo "Rename failed: Invalid original file path: " . htmlspecialchars($oldPath);
    exit;
}

// Get new path in same folder
$folderPath = dirname($oldFullPath);
$newFullPath = $folderPath . '/' . basename($newName);

// Ensure we don't overwrite something
$counter = 1;
$originalName = pathinfo($newFullPath, PATHINFO_FILENAME);
$extension = pathinfo($newFullPath, PATHINFO_EXTENSION);
while (file_exists($newFullPath)) {
    $newFullPath = $folderPath . '/' . $originalName . " ($counter)" . ($extension ? ".$extension" : '');
    $counter++;
}

// Rename the file
if (!rename($oldFullPath, $newFullPath)) {
    http_response_code(500);
    echo "Rename failed.";
    exit;
}

http_response_code(200);
echo "Renamed successfully.";
