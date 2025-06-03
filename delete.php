<?php
$baseDir = realpath(__DIR__ . '/files');

$path = $_GET['path'] ?? '';
if (!$path) {
    http_response_code(400);
    echo "Missing file path.";
    exit;
}

// Remove '/files' prefix and decode
$relativePath = ltrim(str_replace('/files', '', rawurldecode($path)), '/');

// Build full path
$fullPath = realpath($baseDir . '/' . $relativePath);

// Ensure it's within allowed base directory
if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
    http_response_code(403);
    //echo "Access Denied.";
    exit;
}

// Delete logic
if (is_file($fullPath)) {
    if (!unlink($fullPath)) {
        http_response_code(500);
        //echo "File deletion failed.";
        exit;
    }
} elseif (is_dir($fullPath)) {
    function deleteDir($dir) {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                deleteDir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    if (!deleteDir($fullPath)) {
        http_response_code(500);
        exit;
    }
} else {
    http_response_code(400);
    //echo "Invalid file/folder.";
    exit;
}

http_response_code(200);
//echo "Deleted successfully.";
