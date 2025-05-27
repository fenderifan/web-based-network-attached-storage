<?php
$baseDir = realpath(__DIR__ . '/files');

$oldPath = $_POST['oldPath'] ?? '';
$newName = $_POST['newName'] ?? '';

if (!$oldPath || !$newName) {
    http_response_code(400);
    echo "Missing required fields.";
    exit;
}

// Remove '/files' prefix and decode URL
$relativePath = ltrim(str_replace('/files', '', urldecode($oldPath)), '/');

// Resolve to full path
$oldFullPath = realpath($baseDir . '/' . $relativePath);

if (!$oldFullPath || strpos($oldFullPath, $baseDir) !== 0) {
    http_response_code(400);
    echo "Invalid original file path.";
    exit;
}

$folderPath = dirname($oldFullPath);
$extension = pathinfo($oldFullPath, PATHINFO_EXTENSION);

// Append extension if missing in new name
if (!pathinfo($newName, PATHINFO_EXTENSION)) {
    $newName .= '.' . $extension;
}


$newFullPath = $folderPath . '/' . basename($newName);

// If new path is same as old, skip renaming
if (realpath($oldFullPath) === realpath($newFullPath)) {
    http_response_code(200);
    echo "Name unchanged.";
    exit;
}

// Avoid overwriting existing files
$counter = 1;
$originalName = pathinfo($newFullPath, PATHINFO_FILENAME);
while (file_exists($newFullPath)) {
    $newFullPath = $folderPath . '/' . $originalName . " ($counter)" . ($extension ? ".$extension" : '');
    $counter++;
}

// Attempt rename
if (!rename($oldFullPath, $newFullPath)) {
    http_response_code(500);
    echo "Rename failed.";
    exit;
}

http_response_code(200);
echo "Renamed successfully.";
