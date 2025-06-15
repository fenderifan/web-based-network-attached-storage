<?php
require_once __DIR__ . '/logging.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
    exit;
}
function clear_directory_contents($dir) {
    if (!is_dir($dir)) {
        return true;
    }
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            if (!@$action($fileinfo->getRealPath())) {
                throw new Exception("Failed to delete " . $fileinfo->getRealPath());
            }
        }
        return true;
    } catch (Exception $e) {
        write_log("Cleanup Error: " . $e->getMessage());
        return false;
    }
}
$tempDir = __DIR__ . '/files/.tmp';
header('Content-Type: application/json');
if (clear_directory_contents($tempDir)) {
    write_log("Manual cleanup: Temporary upload cache was cleared successfully.");
    echo json_encode(['success' => true, 'message' => 'Temporary upload cache has been cleared.']);
} else {
    http_response_code(500);
    write_log("Manual cleanup: Failed to clear temporary upload cache. Check permissions.");
    echo json_encode(['success' => false, 'message' => 'Could not clear all temporary files. Please check file permissions.']);
}