<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logging.php';

$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

define('IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
define('VIDEO_EXTENSIONS', ['mp4', 'webm', 'ogg', 'mov']);

$baseDir = realpath(__DIR__ . '/files');
$requestedPath = $_GET['path'] ?? '';

if (empty($requestedPath)) {
    http_response_code(400);
    echo "Error: Missing file path.";
    exit;
}

$relativePath = ltrim(str_replace('/files', '', rawurldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    echo "Access Denied: The requested file is not accessible.";
    exit;
}

$filename = basename($fullPath);
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$fileSize = filesize($fullPath);

$mimeTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg',
    'mov' => 'video/quicktime', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

function is_image($ext) {
    return in_array($ext, IMAGE_EXTENSIONS);
}

function is_video($ext) {
    return in_array($ext, VIDEO_EXTENSIONS);
}


if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    // Turn off output buffering to reduce memory overhead
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $fileSize);
    header('Accept-Ranges: bytes');
    
    $disposition = in_array($ext, array_merge(IMAGE_EXTENSIONS, VIDEO_EXTENSIONS)) ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    
    flush();

    //untuk video
    if (is_video($ext)) {
        $streamStartTime = microtime(true);
        $logInterval = 1;
        $lastLogTime = $streamStartTime;

        $is_initial_request = !isset($_SERVER['HTTP_RANGE']) || strpos($_SERVER['HTTP_RANGE'], 'bytes=0-') === 0;
        if ($is_initial_request) {
            write_log(sprintf('Preview Video "%s" (%s)...', $filename, format_bytes($fileSize)));
        }

        $file = @fopen($fullPath, 'rb');
        if ($file) {
            while (!feof($file) && !connection_aborted()) {

                echo fread($file, 2 * 1024 * 1024);
                flush();
                
                $bytesSent = ftell($file);
                $currentTime = microtime(true);

                if ($currentTime - $lastLogTime >= $logInterval) {
                     $ram = get_ram_usage();
                     $cpu = get_cpu_usage();
                     $timeElapsed = $currentTime - $streamStartTime;
                     $speed = $timeElapsed > 0 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0;
                     
                     write_log(sprintf(
                        'Streaming Video "%s" (Cpu: %.1f%%, Ram: %s / %d%%, Speed: %.1f MB/s)',
                        $filename, $cpu, format_ram($ram['used_kb']), $ram['percent'], $speed
                     ));
                     $lastLogTime = $currentTime;
                }
            }
            fclose($file);
        }
    
    //untuk gambar
    } elseif (is_image($ext)) {
        $ram = get_ram_usage();
        $cpu = get_cpu_usage();
        write_log(sprintf(
            'Preview Image "%s" (%s) - CPU: %.1f%%, RAM: %s / %d%%',
            $filename, format_bytes($fileSize), $cpu, format_ram($ram['used_kb']), $ram['percent']
        ));
        readfile($fullPath);
    
    } else {
        readfile($fullPath);
    }
    exit;
}

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
