<?php
$baseDir = realpath(__DIR__ . '/../files');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$subPath = '/' . ltrim(substr($uri, strlen('/files')), '/');
$fullPath = realpath($baseDir . $subPath);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
    http_response_code(403);
    echo "Access Denied.";
    exit;
}

if (is_file($fullPath)) {
    header('Content-Type: ' . mime_content_type($fullPath));
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

$items = scandir($fullPath);
?>

<h4 class="mb-4">Browsing: <code><?= htmlspecialchars('/files' . $subPath) ?></code></h4>
<div class="row g-3">
  <?php foreach ($items as $item): ?>
    <?php if ($item === '.' || $item === '..') continue; ?>
    <?php
      $itemPath = $fullPath . '/' . $item;
      $itemUri = '/files' . rtrim($subPath, '/') . '/' . $item;
    ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card file-item shadow-sm p-3">
        <?php if (is_dir($itemPath)): ?>
          <i class="bi bi-folder-fill text-warning fs-1"></i>
          <h5 class="mt-2"><a href="<?= htmlspecialchars($itemUri) ?>" class="text-decoration-none"><?= htmlspecialchars($item) ?></a></h5>
          <p class="text-muted">Folder</p>
        <?php else: ?>
          <i class="bi bi-file-earmark-fill text-secondary fs-1"></i>
          <h5 class="mt-2"><a href="<?= htmlspecialchars($itemUri) ?>" class="text-decoration-none" target="_blank"><?= htmlspecialchars($item) ?></a></h5>
          <p class="text-muted">File • <?= round(filesize($itemPath) / 1024, 1) ?> KB</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
