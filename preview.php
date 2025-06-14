<?php
// preview.php (Modified for Thumbnail Generation)

require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/config.php';

// --- CONFIGURATION & PATH SETUP ---
$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

$baseDir = realpath(__DIR__ . '/files');
$thumbCacheDir = $baseDir . '/.thumbnails';
$requestedPath = $_GET['path'] ?? '';

if (!$requestedPath) {
    http_response_code(400);
    echo "Missing path.";
    exit;
}

// --- PATH VALIDATION ---
$relativePath = ltrim(str_replace('/files', '', rawurldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    echo "Access Denied.";
    exit;
}

$filename = basename($fullPath);
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$fileSize = filesize($fullPath);

// --- HELPER FUNCTIONS & MIME TYPES ---
$mimeTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg',
    'mov' => 'video/quicktime', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
    'zip' => 'application/zip', 'rar' => 'application/vnd.rar',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

function is_image($ext) {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

function is_video($ext) {
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}

/// --- NEW: THUMBNAIL GENERATION & CACHING (with enhanced error logging) ---
if (isset($_GET['thumb']) && $_GET['thumb'] === '1') {
    // Generate a unique, safe filename for the cached thumbnail
    $thumbCacheFile = $thumbCacheDir . '/' . md5($fullPath) . '.jpg';

    // Create the cache directory if it doesn't exist
    if (!is_dir($thumbCacheDir)) {
        @mkdir($thumbCacheDir, 0777, true);
        @file_put_contents($thumbCacheDir . '/.htaccess', 'Deny from all');
    }

    // If the thumbnail doesn't exist in the cache, create it
    if (!file_exists($thumbCacheFile)) {
        // --- MODIFIED FOR BETTER DEBUGGING ---
        // Explicitly define the path to ffmpeg if you know it, otherwise leave as 'ffmpeg'
        $ffmpeg_path = 'ffmpeg'; // Or use the full path like '/usr/bin/ffmpeg'

        $escapedPath = escapeshellarg($fullPath);
        $escapedThumb = escapeshellarg($thumbCacheFile);
        
        // The command now redirects error output (2>&1) so we can capture it
        $command = "$ffmpeg_path -i $escapedPath -ss 00:00:05 -vframes 1 -q:v 2 $escapedThumb 2>&1";
        
        // Use exec to capture the output and return status
        exec($command, $output, $return_code);

        // If the return code is not 0, it means an error occurred
        if ($return_code !== 0) {
            // Log the detailed error output from FFmpeg
            $error_details = implode("\n", $output);
            write_log("FFmpeg failed for: " . $filename . ". Return code: " . $return_code . ". Output: " . $error_details);
            
            // Serve a placeholder since generation failed
            http_response_code(404);
            echo "Thumbnail generation failed.";
            exit;
        }
    }

    // Serve the thumbnail
    if (file_exists($thumbCacheFile)) {
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($thumbCacheFile));
        readfile($thumbCacheFile);
    } else {
        // This is a fallback in case the file still doesn't exist for some reason
        http_response_code(404);
        write_log("Thumbnail file not found after supposed creation for: " . $filename);
        echo "Thumbnail file not found.";
    }
    exit;
}


// --- RAW FILE STREAMING (FOR VIDEO PLAYER & DOWNLOADS) ---
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    
    // Use 'inline' for media so it plays in the browser, 'attachment' for other files
    if (is_video($ext) || is_image($ext)) {
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    }
    
    // --- CRITICAL FOR VIDEO SEEKING ---
    if (isset($_SERVER['HTTP_RANGE'])) {
        http_response_code(206);
        $range = $_SERVER['HTTP_RANGE'];
        list($type, $range) = explode('=', $range, 2);
        list($start, $end) = explode('-', $range);
        
        $start = intval($start);
        $end = $end ? intval($end) : $fileSize - 1;
        $length = $end - $start + 1;
        
        header('Content-Length: ' . $length);
        header("Content-Range: bytes $start-$end/$fileSize");
        
        $f = fopen($fullPath, 'rb');
        fseek($f, $start);
        echo fread($f, $length);
        fclose($f);
    } else {
        // --- HANDLES INITIAL LOAD (STREAMING) ---
        // MODIFIED: Logging is streamlined to be less intensive.
        // Intensive per-chunk logging has been removed as it slows down streaming.
        header('Content-Length: ' . $fileSize);
        
        $startTime = microtime(true);
        write_log('Started streaming preview for "' . $filename . '"');
        
        readfile($fullPath); // Use readfile for efficient serving.

        $totalTime = microtime(true) - $startTime;
        write_log(sprintf(
            'Finished streaming "%s" (%s) in %.1f sec.',
            $filename, format_bytes(filesize($fullPath)), $totalTime
        ));
    }
    exit;
}

// --- HTML OUTPUT FOR THE MODAL ---
?>
<div class="modal-header">
  <h5 class="modal-title" title="<?= htmlspecialchars($filename) ?>"><?= htmlspecialchars($filename) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body text-center">
<?php if (is_image($ext)): ?>
  <img src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" class="img-fluid" alt="<?= htmlspecialchars($filename) ?>">
  <div class="mt-3 text-center">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download><i class="bi bi-download me-1"></i> Download Image</a>
  </div>

<?php elseif (is_video($ext)): ?>
  <video controls preload="metadata" class="w-100 bg-dark" poster="/preview.php?path=<?= urlencode($requestedPath) ?>&thumb=1">
    <source src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" type="<?= $mime ?>">
    Your browser does not support the video tag.
  </video>
  
  <div class="mt-3 text-center">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download><i class="bi bi-download me-1"></i> Download Video</a>
  </div>

<?php else: ?>
  <div class="p-5 text-center">
      <i class="bi bi-file-earmark-lock fs-1 text-muted"></i>
      <p class="mt-3 text-muted">Live preview is not available for this file type.</p>
      <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-primary" download><i class="bi bi-download me-2"></i>Download File</a>
  </div>
<?php endif; ?>
</div>