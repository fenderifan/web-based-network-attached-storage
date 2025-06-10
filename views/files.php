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

function getIconClassAndColorForFile($filename, $isDir) {
    if ($isDir) {
        return ['bi-folder-fill', 'text-warning']; // Yellow folder
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $map = [
        'jpg'  => ['bi-file-image', 'text-primary'],
        'jpeg' => ['bi-file-image', 'text-primary'],
        'png'  => ['bi-file-image', 'text-primary'],
        'gif'  => ['bi-file-image', 'text-success'],
        'svg'  => ['bi-file-image', 'text-warning'],
        'pdf'  => ['bi-file-earmark-pdf', 'text-danger'],
        'doc'  => ['bi-file-earmark-word', 'text-primary'],
        'docx' => ['bi-file-earmark-word', 'text-primary'],
        'xls'  => ['bi-file-earmark-excel', 'text-success'],
        'xlsx' => ['bi-file-earmark-excel', 'text-success'],
        'ppt'  => ['bi-file-earmark-ppt', 'text-warning'],
        'pptx' => ['bi-file-earmark-ppt', 'text-warning'],
        'txt'  => ['bi-file-earmark-text', 'text-secondary'],
        'zip'  => ['bi-file-earmark-zip', 'text-warning'],
        'rar'  => ['bi-file-earmark-zip', 'text-warning'],
        'mp3'  => ['bi-file-earmark-music', 'text-warning'],
        'wav'  => ['bi-file-earmark-music', 'text-warning'],
        'mp4'  => ['bi-file-earmark-play', 'text-success'],
        'mov'  => ['bi-file-earmark-play', 'text-success'],
        'avi'  => ['bi-file-earmark-play', 'text-success'],
        'php'  => ['bi-file-code', 'text-secondary'],
        'js'   => ['bi-file-code', 'text-secondary'],
        'html' => ['bi-file-code', 'text-secondary'],
        'css'  => ['bi-file-code', 'text-secondary'],
        // add more if needed
    ];

    return $map[$ext] ?? ['bi-file-earmark-text', 'text-secondary']; // default grey icon
}


$items = scandir($fullPath);

// --- START: MODIFIED BREADCRUMB LOGIC ---
$breadcrumbs = [];
$trimmedSubPath = trim($subPath, '/');
if (!empty($trimmedSubPath)) {
    $breadcrumbParts = explode('/', $trimmedSubPath);
    $accumulatedPath = '';
    foreach ($breadcrumbParts as $part) {
        $accumulatedPath .= '/' . $part;
        $breadcrumbs[] = [
            'name' => $part,
            'path' => '/files' . $accumulatedPath
        ];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 gap-2">
  <div class="d-flex align-items-center flex-nowrap gap-2" style="min-width: 0;">
    <a href="/files" class="text-decoration-none fw-bold">Files</a>
    <?php if (count($breadcrumbs) > 1): ?>
      <span>›</span>
      <div class="dropdown">
        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          ...
        </button>
        <ul class="dropdown-menu breadcrumb-dropdown">
          <?php for ($i = 0; $i < count($breadcrumbs) - 1; $i++): ?>
            <li><a class="dropdown-item text-truncate" href="<?= htmlspecialchars($breadcrumbs[$i]['path']) ?>" title="<?= htmlspecialchars($breadcrumbs[$i]['name']) ?>">
              <?= htmlspecialchars($breadcrumbs[$i]['name']) ?>
            </a></li>
          <?php endfor; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if (!empty($breadcrumbs)): ?>
      <span>›</span>
      <span class="fw-bold text-muted text-truncate" title="<?= htmlspecialchars(end($breadcrumbs)['name']) ?>">
        <?= htmlspecialchars(end($breadcrumbs)['name']) ?>
      </span>
    <?php endif; ?>
  </div>
  <button class="btn btn-primary" style="min-width: 150px;" data-bs-toggle="modal" data-bs-target="#actionModal">Upload or Create</button>
</div>
<div class="list-group list-group-flush border rounded">
  <div class="list-group-item d-flex fw-bold text-muted">
  <div class="flex-grow-1">Name</div>
  <div class="d-none d-md-block" style="width: 100px;">Type</div>
  <div class="d-none d-md-block" style="width: 140px;">Date modified</div>
  <div class="d-none d-md-block" style="width: 80px;">Size</div>
  <div style="width: 40px;"></div>
</div>


  <?php foreach ($items as $item): ?>
    <?php if ($item === '.' || $item === '..' || $item === '.tmp') continue; // MODIFIED LINE ?>
    <?php
      $itemPath = $fullPath . '/' . $item;
      $itemUri = '/files' . rtrim($subPath, '/') . '/' . $item;
      $isDir = is_dir($itemPath);
      list($iconClass, $colorClass) = getIconClassAndColorForFile($item, $isDir);
      $size = $isDir ? '' : round(filesize($itemPath) / 1048576, 2) . ' MB';
      $type = $isDir ? 'Folder' : pathinfo($item, PATHINFO_EXTENSION);
      $download = $isDir ? '' : '<li><a class="dropdown-item" href="' . htmlspecialchars('/preview.php?path=' . rawurlencode($itemUri) . '&raw=1') . '" download>Download</a></li>';
      $dateModified = date("d/m/y H:i", filemtime($itemPath));
    ?>
    <div class="list-group-item d-flex align-items-center">
  <div class="flex-grow-1 d-flex align-items-center overflow-hidden" style="min-width: 0;">
  <i class="bi <?= $iconClass ?> fs-5 me-2 flex-shrink-0 <?= $colorClass ?>"></i>
  <a href="<?= $isDir ? htmlspecialchars($itemUri) : '#' ?>"
     class="text-decoration-none d-flex align-items-center w-100 overflow-hidden <?= $isDir ? '' : 'preview-link' ?>"
     <?= $isDir ? '' : 'data-bs-toggle="modal" data-bs-target="#previewModal" data-preview="' . htmlspecialchars('/preview.php?path=' . rawurlencode($itemUri)) . '"' ?>>
     <div class="text-truncate truncate-custom" title="<?= htmlspecialchars($item) ?>">
  <?= htmlspecialchars($item) ?>
</div>


  </a>
</div>



  <div class="d-none d-md-block text-muted small" style="width: 100px;"><?= $type ?></div>

  <div class="d-none d-md-block text-muted small" style="width: 140px;"><?= $dateModified ?></div>

  <div class="d-none d-md-block text-muted small" style="width: 80px;"><?= $size ?></div>

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