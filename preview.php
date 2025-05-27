<?php
$baseDir = realpath(__DIR__ . '/files');

$requestedPath = $_GET['path'] ?? '';

if (!$requestedPath) {
    http_response_code(400);
    echo "Missing path.";
    exit;
}

$relativePath = ltrim(str_replace('/files', '', urldecode($requestedPath)), '/');
$fullPath = realpath($baseDir . '/' . $relativePath);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(403);
    echo "Access Denied.";
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$filename = basename($fullPath);

function is_image($ext) {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

function is_video($ext) {
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}

?>

<div class="modal-header">
  <h5 class="modal-title"><?= htmlspecialchars($filename) ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body text-center">

<?php if (is_image($ext)): ?>
  <img src="<?= htmlspecialchars($requestedPath) ?>" class="img-fluid" alt="<?= htmlspecialchars($filename) ?>">
<?php elseif (is_video($ext)): ?>
  <video controls class="w-100">
    <source src="<?= htmlspecialchars($requestedPath) ?>" type="video/<?= $ext ?>">
    Your browser does not support the video tag.
  </video>
<?php else: ?>
  <p class="text-muted">Preview not available for this file type.</p>
  <a href="<?= htmlspecialchars($requestedPath) ?>" class="btn btn-primary" download>Download</a>
<?php endif; ?>

</div>
