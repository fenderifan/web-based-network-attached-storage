<?php
// update.php

// --- CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Set a reasonable execution time
// START: MODIFIED CODE
date_default_timezone_set('Asia/Jakarta'); // Set timezone to match your local time
// END: MODIFIED CODE

// --- PATH SETUP ---
$baseDir = __DIR__ . '/files';
$tempDir = __DIR__ . '/files/.tmp';

$oldPath = $_POST['oldPath'] ?? '';
$newName = $_POST['newName'] ?? '';

if (!$oldPath || !$newName) {
    http_response_code(400);
    echo "Missing required fields.";
    exit;
}

// Remove '/files' prefix and decode URL
$relativePath = ltrim(str_replace('/files', '', rawurldecode($oldPath)), '/');

// Resolve to full path
$oldFullPath = realpath($baseDir . '/' . $relativePath);

if (!$oldFullPath || strpos($oldFullPath, $baseDir) !== 0) {
    http_response_code(400);
    echo "Invalid original file path.";
    exit;
}

$folderPath = dirname($oldFullPath);
$newFullPath = $folderPath . '/' . basename($newName);

// If new path is same as old, skip renaming
if ($oldFullPath === $newFullPath) {
    http_response_code(200);
    echo "Name unchanged.";
    exit;
}

// Avoid overwriting existing files
if (file_exists($newFullPath)) {
    http_response_code(409); // Conflict
    echo "A file with that name already exists.";
    exit;
}

// --- START: FIX FOR DATE MODIFIED ---
// 1. Get the original modification time BEFORE renaming.
$originalMtime = filemtime($oldFullPath);
// --- END: FIX FOR DATE MODIFIED ---

// Attempt rename
if (rename($oldFullPath, $newFullPath)) {
    // --- START: FIX FOR DATE MODIFIED ---
    // 2. If rename is successful, re-apply the original modification time.
    touch($newFullPath, $originalMtime);
    // --- END: FIX FOR DATE MODIFIED ---
    
    http_response_code(200);
    echo "Renamed successfully.";
} else {
    http_response_code(500);
    echo "Rename failed.";
    exit;
}