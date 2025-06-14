<?php
require_once __DIR__ . '/logging.php';

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
write_log('Loading Preview "' . $filename . '"');


$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$fileSize = filesize($fullPath);

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

if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    
    if (is_video($ext)) {
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    }
    
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
        header('Content-Length: ' . $fileSize);
        readfile($fullPath);
    }
    write_log('Preview Loaded "' . $filename . '"');
    exit;
}
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
  <video controls preload="metadata" class="w-100 bg-dark">
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
