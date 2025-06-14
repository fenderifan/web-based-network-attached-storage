<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logging.php';

// Load settings and set timezone for accurate logs
$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

// Define constants for file type checks
define('IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
define('VIDEO_EXTENSIONS', ['mp4', 'webm', 'ogg', 'mov']);

// --- Path and Security Validation ---
$baseDir = realpath(__DIR__ . '/files');
$requestedPath = $_GET['path'] ?? '';

if (empty($requestedPath)) {
    http_response_code(400);
    echo "Error: Missing file path.";
    exit;
}

// Sanitize and resolve the real path to prevent directory traversal
$relativePath = ltrim(str_replace('/files', '', rawurldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

// Security check: Ensure the requested file is within the designated 'files' directory
if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    echo "Access Denied: The requested file is not accessible.";
    exit;
}

// --- File Metadata ---
$filename = basename($fullPath);
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$fileSize = filesize($fullPath);

// MIME types for common formats
$mimeTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg',
    'mov' => 'video/quicktime', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

// Helper function to check if the extension is an image
function is_image($ext) {
    return in_array($ext, IMAGE_EXTENSIONS);
}

// Helper function to check if the extension is a video
function is_video($ext) {
    return in_array($ext, VIDEO_EXTENSIONS);
}


// --- Raw File Streaming with Logging ---
// This block handles direct file delivery for images and videos.
// It's triggered by adding `&raw=1` to the URL.
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    // Turn off output buffering to reduce memory overhead
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set appropriate content headers
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $fileSize);
    header('Accept-Ranges: bytes'); // Essential for video seeking
    
    // Suggest 'inline' for media types that the browser can display directly
    $disposition = in_array($ext, array_merge(IMAGE_EXTENSIONS, VIDEO_EXTENSIONS)) ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    
    // Flush all headers to the client
    flush();

    // --- LOGIC FOR VIDEO STREAMING ---
    if (is_video($ext)) {
        $streamStartTime = microtime(true);
        $logInterval = 1; // Log every 1 second
        $lastLogTime = $streamStartTime;
        $statsLog = [];

        // Check if this is the initial request for the video (not a range request for seeking)
        $is_initial_request = !isset($_SERVER['HTTP_RANGE']) || strpos($_SERVER['HTTP_RANGE'], 'bytes=0-') === 0;
        if ($is_initial_request) {
            write_log(sprintf('Streaming preview for "%s" (%s)...', $filename, format_bytes($fileSize)));
        }

        $file = @fopen($fullPath, 'rb');
        if ($file) {
            while (!feof($file) && !connection_aborted()) {
                // Read and send a chunk of the file
                echo fread($file, 1024 * 1024); // 1MB chunks
                flush();
                
                $bytesSent = ftell($file);
                $currentTime = microtime(true);

                // Log stats periodically
                if ($currentTime - $lastLogTime >= $logInterval) {
                     $ram = get_ram_usage();
                     $cpu = get_cpu_usage();
                     $timeElapsed = $currentTime - $streamStartTime;
                     $speed = $timeElapsed > 0 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0; // MB/s

                     $currentStats = [
                        'cpu' => $cpu,
                        'ram_kb' => $ram['used_kb'],
                        'ram_pct' => $ram['percent'],
                        'speed_mbps' => $speed
                     ];
                     $statsLog[] = $currentStats;
                     
                     write_log(sprintf(
                        'Streaming "%s" (Cpu: %.1f%%, Ram: %s / %d%%, Speed: %.1f MB/s)',
                        $filename, $cpu, format_ram($ram['used_kb']), $ram['percent'], $speed
                     ));
                     $lastLogTime = $currentTime;
                }
            }
            fclose($file);
        }

        $totalTime = microtime(true) - $streamStartTime;
        // Only log the finish and stats message if the stream was long enough to have stats
        if (!empty($statsLog)) {
            write_log(sprintf('Finished streaming "%s" in %.1f sec', $filename, $totalTime));

            // Log Peak/Average stats for the entire duration
            $cpuStats = calculate_stats(array_column($statsLog, 'cpu'));
            $ramPctStats = calculate_stats(array_column($statsLog, 'ram_pct'));
            $ramSizeStats = calculate_stats(array_column($statsLog, 'ram_kb'));
            $speedStats = calculate_stats(array_column($statsLog, 'speed_mbps'));
            write_log(sprintf(
                'Streaming Stats (Total Peak/Avg): CPU (%.1f%%/%.1f%%), RAM (%s/%s | %d%%/%d%%), Speed (%.1f MBps/%.1f MBps)',
                $cpuStats['peak'], $cpuStats['avg'],
                format_ram($ramSizeStats['peak']), format_ram($ramSizeStats['avg']),
                $ramPctStats['peak'], $ramPctStats['avg'],
                $speedStats['peak'], $speedStats['avg']
            ));
        }
    
    // --- LOGIC FOR IMAGE PREVIEW ---
    } elseif (is_image($ext)) {
        $ram = get_ram_usage();
        $cpu = get_cpu_usage();
        write_log(sprintf(
            'Previewing image "%s" (%s) - CPU: %.1f%%, RAM: %s / %d%%',
            $filename, format_bytes($fileSize), $cpu, format_ram($ram['used_kb']), $ram['percent']
        ));
        readfile($fullPath);
    
    // --- FALLBACK FOR OTHER FILE TYPES ---
    } else {
        // No special logging needed for non-media types
        readfile($fullPath);
    }
    
    exit;
}

// --- HTML Modal Generation ---
// This part generates the HTML content for the preview modal window.
// No changes are needed here.
?>

<div class="modal-header">
  <h5 class="modal-title text-truncate" title="<?= htmlspecialchars($filename) ?>"><?= htmlspecialchars($filename) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body text-center">
<?php if (is_image($ext)): ?>
  <img src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" class="img-fluid" alt="<?= htmlspecialchars($filename) ?>">
  <div class="mt-3">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download>
      <i class="bi bi-download me-1"></i> Download Image
    </a>
  </div>

<?php elseif (is_video($ext)): ?>
  <video controls preload="metadata" class="w-100 bg-dark" style="max-height: calc(100vh - 210px);">
    <source src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" type="<?= $mime ?>">
    Your browser does not support the video tag.
  </video>
  <div class="mt-3">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download>
      <i class="bi bi-download me-1"></i> Download Video
    </a>
  </div>

<?php else: ?>
  <div class="p-5">
      <i class="bi bi-file-earmark-lock fs-1 text-muted"></i>
      <p class="mt-3 text-muted">Live preview is not available for this file type.</p>
      <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-primary" download>
        <i class="bi bi-download me-2"></i>Download File
      </a>
  </div>
<?php endif; ?>
</div>
