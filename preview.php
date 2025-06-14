<?php
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/config.php';

// Set timezone for consistent logs
$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

// --- Path and Security Validation ---
$baseDir = realpath(__DIR__ . '/files');
$requestedPath = $_GET['path'] ?? '';

if (!$requestedPath) {
    http_response_code(400);
    echo "Missing path.";
    exit;
}

$relativePath = ltrim(str_replace('/files', '', rawurldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

// Security check: ensure the resolved path is within the base directory and is a file
if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    write_log('Access Denied for path: ' . $requestedPath);
    echo "Access Denied.";
    exit;
}

$filename = basename($fullPath);
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$fileSize = filesize($fullPath);

// --- MIME Type Resolution ---
$mimeTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg',
    'mov' => 'video/quicktime', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
    'zip' => 'application/zip', 'rar' => 'application/vnd.rar',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

// --- Helper Functions ---
function is_image($ext) {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

function is_video($ext) {
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}

// --- Raw File Output Logic ---
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    
    // Set common headers for streaming and content type
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    
    // Set disposition based on file type
    if (is_video($ext) || is_image($ext)) {
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    }
    
    // --- THIS BLOCK IS CRITICAL FOR VIDEO SEEKING (RANGE REQUESTS) ---
    if (isset($_SERVER['HTTP_RANGE'])) {
        http_response_code(206); // Partial Content
        
        // Parse the range header
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        list($start_str, $end_str) = explode('-', $range);
        
        $start = intval($start_str);
        $end = $end_str ? intval($end_str) : $fileSize - 1;
        $length = $end - $start + 1;
        
        // Set range-specific headers
        header('Content-Length: ' . $length);
        header("Content-Range: bytes $start-$end/$fileSize");
        
        // --- Logging and Chunked Streaming for Range Requests ---
        set_time_limit(600);
        $startTime = microtime(true);
        $lastLogTime = $startTime; // Initialize with start time to prevent immediate log
        $bytesSent = 0;

        // Use format_bytes for human-readable logging
        write_log(sprintf(
            'Streaming video range for "%s" (Range: %s - %s of %s)',
            $filename, format_bytes($start), format_bytes($end), format_bytes($fileSize)
        ));

        $f = @fopen($fullPath, 'rb');
        if (!$f) {
            http_response_code(500);
            write_log("Failed to open file for range streaming: " . $fullPath);
            exit;
        }

        fseek($f, $start);
        $bytesRemaining = $length;

        while (!feof($f) && $bytesRemaining > 0 && connection_status() === 0) {
            $chunkSize = min(8192, $bytesRemaining);
            $chunk = fread($f, $chunkSize);
            
            if ($chunk === false) {
                write_log("Failed to read chunk during range streaming: " . $fullPath);
                break;
            }
            
            echo $chunk;
            flush();
            
            $chunkLength = strlen($chunk);
            $bytesSent += $chunkLength;
            $bytesRemaining -= $chunkLength;

            // Log progress every second
            $currentTime = microtime(true);
            if ($currentTime - $lastLogTime >= 1) {
                $ram = get_ram_usage();
                $cpu = get_cpu_usage();
                $timeElapsed = $currentTime - $startTime;
                $speed = $timeElapsed > 0.1 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0; // MB/s

                write_log(sprintf(
                    'Streaming Video "%s" (Cpu: %.1f%%, Ram: %s / %d%%, Speed: %.1f MB/s, Sent: %s)',
                    $filename, $cpu, format_ram($ram['used_kb']), $ram['percent'], $speed, format_bytes($bytesSent)
                ));
                $lastLogTime = $currentTime;
            }
        }
        
        fclose($f);
        
        $totalTime = microtime(true) - $startTime;
        write_log(sprintf(
            'Finished streaming range for "%s" (%s) in %.1f sec.',
            $filename, format_bytes($bytesSent), $totalTime
        ));

    } else {
        // --- THIS BLOCK HANDLES THE INITIAL LOAD / FULL DOWNLOAD WITH LOGGING ---
        header('Content-Length: ' . $fileSize);
        
        set_time_limit(600);
        $startTime = microtime(true);
        $lastLogTime = $startTime; // Initialize with start time to prevent immediate log
        $bytesSent = 0;
        
        write_log('Loading preview for "' . $filename . '"');

        $f = @fopen($fullPath, 'rb');
        if (!$f) {
            http_response_code(500);
            write_log("Failed to open file for buffering: " . $fullPath);
            exit;
        }

        while (!feof($f) && connection_status() === 0) {
            $chunk = fread($f, 8192);
            if ($chunk === false) {
                 write_log("Failed to read chunk while buffering: " . $fullPath);
                 break;
            }
            
            echo $chunk; // Send the chunk immediately
            flush();     // Ensure the output is sent to the browser
            
            $bytesSent += strlen($chunk);

            // Log progress every second
            $currentTime = microtime(true);
            if ($currentTime - $lastLogTime >= 1) {
                $ram = get_ram_usage();
                $cpu = get_cpu_usage();
                $timeElapsed = $currentTime - $startTime;
                $speed = $timeElapsed > 0.1 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0; // MB/s

                write_log(sprintf(
                    'Buffering Preview "%s" (Cpu: %.1f%%, Ram: %s / %d%%, Speed: %.1f MB/s)',
                    $filename, $cpu, format_ram($ram['used_kb']), $ram['percent'], $speed
                ));
                $lastLogTime = $currentTime;
            }
        }
        fclose($f);
        
        $totalTime = microtime(true) - $startTime;
        write_log(sprintf(
            'Finished Loaded "%s" (%s) in %.1f sec.',
            $filename, format_bytes($bytesSent), $totalTime
        ));
    }
    exit; // Stop script execution after sending the raw file
}
?>

<!-- HTML for displaying the preview in a modal -->
<div class="modal-header">
  <h5 class="modal-title" title="<?= htmlspecialchars($filename) ?>"><?= htmlspecialchars($filename) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body text-center p-0">
<?php if (is_image($ext)): ?>
  <img src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" class="img-fluid" alt="<?= htmlspecialchars($filename) ?>" style="max-height: 80vh;">

  <div class="mt-3 mb-3 text-center">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download>
      <i class="bi bi-download me-1"></i> Download Image
    </a>
  </div>

<?php elseif (is_video($ext)): ?>
  <video controls preload="metadata" class="w-100 bg-dark" style="max-height: 80vh;">
    <source src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" type="<?= $mime ?>">
    Your browser does not support the video tag.
  </video>
  
  <div class="mt-3 mb-3 text-center">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download>
      <i class="bi bi-download me-1"></i> Download Video
    </a>
  </div>

<?php else: ?>
  <div class="p-5 text-center">
      <i class="bi bi-file-earmark-lock fs-1 text-muted"></i>
      <p class="mt-3 text-muted">Live preview is not available for this file type.</p>
      <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-primary" download>
        <i class="bi bi-download me-2"></i>Download File
      </a>
  </div>
<?php endif; ?>
</div>
