<?php
    require_once __DIR__ . '/logging.php';
    $baseDir = realpath(__DIR__ . '/files');
    $oldPath = $_POST['oldPath'] ?? '';
    $newName = $_POST['newName'] ?? '';
    if (!$oldPath || !$newName) {
        http_response_code(400);
        echo "Missing required fields.";
        exit;
    }
    $relativePath = ltrim(str_replace('/files', '', rawurldecode($oldPath)), '/');
    $oldName = basename($relativePath);
    $oldFullPath = realpath($baseDir . '/' . $relativePath);
    if (!$oldFullPath || strpos($oldFullPath, $baseDir) !== 0) {
        http_response_code(400);
        echo "Invalid original file path.";
        exit;
    }
    $folderPath = dirname($oldFullPath);
    $newFullPath = $folderPath . '/' . basename($newName);
    if ($oldFullPath === $newFullPath) {
        http_response_code(200);
        echo "Name unchanged.";
        exit;
    }
    if (file_exists($newFullPath)) {
        http_response_code(409);
        echo "A file with that name already exists.";
        exit;
    }
    if (!rename($oldFullPath, $newFullPath)) {
        http_response_code(500);
        echo "Rename failed.";
        exit;
    }
    write_log('Renamed from "' . $oldName . '" to "' . basename($newName) . '"');
    http_response_code(200);
    echo "Renamed successfully.";
?>