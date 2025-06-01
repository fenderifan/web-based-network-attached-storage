<?php
$baseDir = realpath(__DIR__ . '/files');
$requestedPath = $_GET['path'] ?? '';

if (!$requestedPath) {
    http_response_code(400);
    echo "Missing path.";
    exit;
}

// Bersihkan dan ambil path relatif
$relativePath = ltrim(str_replace('/files', '', urldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

// Cek keamanan dan apakah file valid
if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    echo "Access Denied.";
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$filename = basename($fullPath);

// Fungsi pengecekan tipe file
function is_image($ext) {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

function is_video($ext) {
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}

// Jika dipanggil langsung untuk file (img src/video src), tampilkan kontennya
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile($fullPath);
        exit;
    }
}
?>

<!-- Tampilan HTML -->
<div class="modal-header">
  <h5 class="modal-title"><?= htmlspecialchars($filename) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body text-center">
<?php if (is_image($ext)): ?>
  <img src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" class="img-fluid" alt="<?= htmlspecialchars($filename) ?>">
<?php elseif (is_video($ext)): ?>
  <video controls class="w-100">
    <source src="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" type="video/<?= $ext ?>">
    Your browser does not support the video tag.
  </video>
<?php else: ?>
  <p class="text-muted">Preview not available for this file type.</p>
  <a href="/preview.php?path=<?= urlencode($requestedPath) ?>&raw=1" class="btn btn-primary" download>Download</a>
<?php endif; ?>
</div>
