<?php
    require_once __DIR__ . '/logging.php';
    require_once __DIR__ . '/config.php';
    $settings = load_settings();
    date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');
    $baseDir = realpath(__DIR__ . '/files');
    $input = json_decode(file_get_contents('php://input'), true);
    $path = $input['path'] ?? '';
    if (!$path) {
        http_response_code(400);
        echo "Missing file path.";
        exit;
    }
    $relativePath = ltrim(str_replace('/files', '', rawurldecode($path)), '/');
    $itemName = basename($relativePath);
    $fullPath = realpath($baseDir . '/' . $relativePath);
    if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
        http_response_code(403);
        echo "Access Denied.";
        exit;
    }
    if (is_file($fullPath)) {
        if (!unlink($fullPath)) {
            http_response_code(500);
            echo "File deletion failed.";
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
            echo "Failed to delete folder.";
            exit;
        }
    } else {
        http_response_code(400);
        echo "Invalid file/folder.";
        exit;
    }
    write_log('Deleted "' . $itemName . '"');
    http_response_code(200);
    echo "Deleted successfully.";
?>