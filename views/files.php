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

// Breadcrumb parts
$breadcrumbParts = explode('/', trim($subPath, '/'));
$breadcrumbs = [];
$accumulatedPath = '';

foreach ($breadcrumbParts as $part) {
    $accumulatedPath .= '/' . $part;
    $breadcrumbs[] = [
        'name' => $part,
        'path' => '/files' . $accumulatedPath
    ];
}
?>

<!-- Breadcrumb navigation + button -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div class="d-flex align-items-center flex-wrap gap-2">
    <a href="/files" class="text-decoration-none fw-bold">Files</a>
    <?php if (count($breadcrumbs) > 4): ?>
      <span>›</span>
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">...</button>
        <ul class="dropdown-menu">
          <?php for ($i = 0; $i < count($breadcrumbs) - 2; $i++): ?>
            <li><a class="dropdown-item" href="<?= htmlspecialchars($breadcrumbs[$i]['path']) ?>">
              <?= htmlspecialchars($breadcrumbs[$i]['name']) ?>
            </a></li>
          <?php endfor; ?>
        </ul>
      </div>
      <span>›</span>
      <a href="<?= htmlspecialchars($breadcrumbs[count($breadcrumbs) - 2]['path']) ?>" class="text-decoration-none">
        <?= htmlspecialchars($breadcrumbs[count($breadcrumbs) - 2]['name']) ?>
      </a>
      <span>›</span>
      <span class="fw-bold text-muted"><?= htmlspecialchars(end($breadcrumbs)['name']) ?></span>
    <?php else: ?>
      <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <span>›</span>
        <?php if ($index === count($breadcrumbs) - 1): ?>
          <span class="fw-bold text-muted"><?= htmlspecialchars($crumb['name']) ?></span>
        <?php else: ?>
          <a href="<?= htmlspecialchars($crumb['path']) ?>" class="text-decoration-none">
            <?= htmlspecialchars($crumb['name']) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <!-- Right-aligned custom button -->
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#actionModal">Upload or Create</button>
</div>

<!-- File list -->
<div class="list-group list-group-flush border rounded">
  <div class="list-group-item d-flex fw-bold text-muted">
  <div class="flex-grow-1">Name</div>
  <div class="d-none d-md-block" style="width: 100px;">Type</div>
  <div class="d-none d-md-block" style="width: 140px;">Date modified</div>
  <div style="width: 80px;">Size</div>
  <div style="width: 40px;"></div>
</div>


  <?php foreach ($items as $item): ?>
    <?php if ($item === '.' || $item === '..') continue; ?>
    <?php
      $itemPath = $fullPath . '/' . $item;
      $itemUri = '/files' . rtrim($subPath, '/') . '/' . $item;
      $isDir = is_dir($itemPath);
      $icon = $isDir ? 'bi-folder-fill text-warning' : 'bi-file-earmark text-secondary';
      $size = $isDir ? '' : round(filesize($itemPath) / 1048576, 2) . ' MB';
      $type = $isDir ? 'Folder' : pathinfo($item, PATHINFO_EXTENSION);
      $download = $isDir ? '' : '<li><a class="dropdown-item" href="' . htmlspecialchars('/preview.php?path=' . rawurlencode($itemUri) . '&raw=1') . '" download>Download</a></li>';
      $dateModified = date("d/m/y H:i", filemtime($itemPath));
    ?>
    <div class="list-group-item d-flex align-items-center">
  <!-- Name column (set max-width and truncate properly) -->
<div class="flex-grow-1 d-flex align-items-center overflow-hidden" style="min-width: 0;">
  <i class="bi <?= $icon ?> fs-5 me-2 flex-shrink-0"></i>
  <a href="<?= $isDir ? htmlspecialchars($itemUri) : '#' ?>"
     class="text-decoration-none d-flex align-items-center w-100 overflow-hidden"
     <?= $isDir ? '' : 'data-bs-toggle="modal" data-bs-target="#previewModal" data-preview="' . htmlspecialchars('/preview.php?path=' . rawurlencode($itemUri)) . '"' ?>>
     <div class="text-truncate truncate-custom" title="<?= htmlspecialchars($item) ?>">
  <?= htmlspecialchars($item) ?>
</div>


  </a>
</div>



  <!-- Type -->
  <div class="d-none d-md-block text-muted small" style="width: 100px;"><?= $type ?></div>

  <!-- Date Modified -->
  <div class="d-none d-md-block text-muted small" style="width: 140px;"><?= $dateModified ?></div>

  <!-- Size -->
  <div class="text-muted small" style="width: 80px;"><?= $size ?></div>

  <!-- Actions -->
  <div style="width: 40px;" class="text-end">
    <div class="dropdown">
      <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-three-dots-vertical"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?= $download ?>
        <a href="#" class="copy-path-btn dropdown-item" data-path="<?= htmlspecialchars($itemUri) ?>">Copy path</a>
        <li><a class="dropdown-item rename-button" href="#" data-path="<?= htmlspecialchars($itemUri) ?>" data-name="<?= htmlspecialchars($item) ?>">Rename</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger delete-btn" href="#" data-path="<?= htmlspecialchars($itemUri) ?>">Delete</a></li>
      </ul>
    </div>
  </div>
</div>

  <?php endforeach; ?>
</div>
