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


<div class="list-group">
  <?php foreach ($items as $item): ?>
    <?php if ($item === '.' || $item === '..') continue; ?>
    <?php
      $itemPath = $fullPath . '/' . $item;
      $itemUri = '/files' . rtrim($subPath, '/') . '/' . $item;
      $isDir = is_dir($itemPath);
      $icon = $isDir ? 'bi-folder-fill text-warning' : 'bi-file-earmark-fill text-secondary';
      $typeText = $isDir ? 'Folder' : 'File • ' . round(filesize($itemPath) / 1024, 1) . ' KB';
    ?>
    <div class="list-group-item d-flex justify-content-between align-items-center">
  <a href="<?= htmlspecialchars($itemUri) ?>" class="d-flex align-items-center gap-3 text-decoration-none flex-grow-1">
    <i class="bi <?= $icon ?> fs-4"></i>
    <div>
      <div class="fw-semibold"><?= htmlspecialchars($item) ?></div>
      <small class="text-muted"><?= $typeText ?></small>
    </div>
  </a>

  <div class="dropdown">
    <button class="btn btn-sm btn-light" type="button" id="dropdownMenuButton<?= md5($itemUri) ?>" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton<?= md5($itemUri) ?>">
      <li>
  <a href="#" class="dropdown-item rename-button"
     data-path="<?= htmlspecialchars($itemUri) ?>"
     data-name="<?= htmlspecialchars($item) ?>">
    Rename
  </a>
</li>

      <li>
  <a href="#" class="dropdown-item text-danger delete-btn"
     data-path="<?= htmlspecialchars($itemUri) ?>">
    Delete
  </a>
</li>

      <li><a class="dropdown-item" href="details.php?path=<?= urlencode($itemUri) ?>">Details</a></li>
    </ul>
  </div>
</div>


  <?php endforeach; ?>
</div>

