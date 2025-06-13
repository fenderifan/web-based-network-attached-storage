<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logging.php';

$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

$baseDir = realpath(__DIR__ . '/files');
$requestedPath = $_GET['path'] ?? '';

if (!$requestedPath) {
    http_response_code(400);
    echo "Missing path.";
    exit;
}

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

$mimeTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg', 'ogv' => 'video/ogg',
    'mov' => 'video/quicktime', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
    'zip' => 'application/zip', 'rar' => 'application/vnd.rar',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

function is_image($ext) {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

function is_video($ext) {
    return in_array($ext, ['mp4', 'webm', 'ogg', 'ogv', 'mov']);
}

function format_bytes_preview($bytes) {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// --- RAW FILE STREAMING with LOGGING ---
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    
    if (is_video($ext)) {
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    }

    $previewStartTime = microtime(true);
    
    // Handle byte-range requests for seeking
    if (isset($_SERVER['HTTP_RANGE'])) {
        http_response_code(206);
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
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
        // Full file download with progress logging
        header('Content-Length: ' . $fileSize);

        $logInterval = 1; // Log every 1 second
        $lastLogTime = $previewStartTime;
        $bytesSent = 0;
        
        $file = @fopen($fullPath, 'rb');
        if ($file) {
            $initialRam = get_ram_usage();
            $initialCpu = get_cpu_usage();
            write_log(sprintf(
                'Loading Preview "%s" (Cpu: %s, Ram: %.1fGB/%d%%)',
                $filename, $initialCpu, $initialRam['size'], $initialRam['percent']
            ));

            while (!feof($file) && !connection_aborted()) {
                $chunk = fread($file, 1024 * 1024); // Read 1MB chunks
                echo $chunk;
                @ob_flush();
                flush();
                
                $bytesSent += strlen($chunk);
                $currentTime = microtime(true);
                
                if ($currentTime - $lastLogTime >= $logInterval) {
                     $ram = get_ram_usage();
                     $cpu = get_cpu_usage();
                     $timeElapsed = $currentTime - $previewStartTime;
                     $speed = $timeElapsed > 0.1 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0; // MB/s
                     
                     write_log(sprintf(
                        'Loading Preview "%s" (Cpu: %s, Ram: %.1fGB/%d%%, Speed: %.2f MB/s)',
                        $filename, $cpu, $ram['size'], $ram['percent'], $speed
                     ));
                     $lastLogTime = $currentTime;
                }
            }
            fclose($file);
        }
    }

    $totalTime = microtime(true) - $previewStartTime;
    write_log(sprintf(
        'Loaded Preview "%s" (%s) in %.1f sec',
        $filename, format_bytes_preview($fileSize), $totalTime
    ));
    exit;
}

// --- MODAL HTML GENERATION ---
write_log('Generating preview for "' . $filename . '"');
?>

<div class="modal-header">
  <h5 class="modal-title" title="<?= htmlspecialchars($filename) ?>"><?= htmlspecialchars($filename) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body text-center">
<?php if (is_image($ext)): ?>
  <img src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" class="img-fluid" alt="<?= htmlspecialchars($filename) ?>">
  <div class="mt-3 text-center">
    <a href="/download.php?path=<?= urlencode($requestedPath) ?>" class="btn btn-sm btn-outline-primary" download>
      <i class="bi bi-download me-1"></i> Download Image
    </a>
  </div>

<?php elseif (is_video($ext)): ?>
  <!-- Using preload="auto" encourages the browser to download the full video -->
  <video controls preload="auto" class="w-100 bg-dark">
    <source src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" type="<?= $mime ?>">
    Your browser does not support the video tag.
  </video>
  <div class="mt-3 text-center">
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
