<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logging.php';

$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

$baseDir = realpath(__DIR__ . '/files');
$requestedPath = $_GET['path'] ?? '';

if (!$requestedPath) {
    http_response_code(400);
    exit("Missing path.");
}

$relativePath = ltrim(str_replace('/files', '', rawurldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    exit("Access Denied.");
}

$filename = basename($fullPath);
$filesize = filesize($fullPath);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Accept-Ranges: bytes');

$downloadStartTime = microtime(true);
$logInterval = 1; // Log every 1 second
$lastLogTime = $downloadStartTime;
$bytesSent = 0;

$file = @fopen($fullPath, 'rb');
if ($file) {
    while (!feof($file) && !connection_aborted()) {
        $chunk = fread($file, 1024 * 1024); // Read 1MB chunks
        echo $chunk;
        flush(); // Flush buffer to client
        
        $bytesSent += strlen($chunk);
        $currentTime = microtime(true);
        if ($currentTime - $lastLogTime >= $logInterval) {
             $ram = get_ram_usage();
             $cpu = get_cpu_usage();
             $timeElapsed = $currentTime - $downloadStartTime;
             $speed = $timeElapsed > 0 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0; // MB/s
             
             write_log(sprintf(
                'Downloading "%s" (Cpu Usage : %s, Ram Usage : %.1f GB / %d %%, Transfer Speed : %.1f MB/s)',
                $filename,
                $cpu,
                $ram['size'],
                $ram['percent'],
                $speed
             ));
             $lastLogTime = $currentTime;
        }
    }
    fclose($file);
}

$totalTime = microtime(true) - $downloadStartTime;

function format_bytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// Final download log entry
write_log(sprintf(
    'Downloaded "%s" (%s) in %.0f sec',
    $filename,
    format_bytes($filesize),
    $totalTime
));

exit;
