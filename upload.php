<?php
// Debug and config
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('file_uploads', '1');
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');

// Setup paths
$baseDir = __DIR__ . '/files';
$targetFolder = $_POST['targetFolder'] ?? '/';
$targetFolder = trim($targetFolder, '/');
$targetPath = $baseDir . ($targetFolder ? "/$targetFolder" : '');

if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777, true);
}

// Validate upload
if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo "No file uploaded or upload error.";
    exit;
}

$originalName = basename($_FILES['fileToUpload']['name']);
$extension = pathinfo($originalName, PATHINFO_EXTENSION);
$nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

$destination = $targetPath . '/' . $originalName;

// Auto-rename logic
$counter = 1;
while (file_exists($destination)) {
    $newName = $nameWithoutExt . " ($counter)" . ($extension ? '.' . $extension : '');
    $destination = $targetPath . '/' . $newName;
    $counter++;
}

if (!move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $destination)) {
    error_log("Upload error: " . print_r($_FILES['fileToUpload'], true));
    http_response_code(500);
    echo "Failed to move uploaded file.";
    exit;
}

echo basename($destination); // Optional: return final filename
