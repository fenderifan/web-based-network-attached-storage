<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    .file-item {
      cursor: pointer;
    }
    .breadcrumb a {
      text-decoration: none;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-light bg-light d-md-none">
    <div class="container-fluid">
      <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
        <i class="bi bi-list fs-3"></i>
      </button>
      <span class="navbar-brand mb-0 h1">File Manager</span>
    </div>
  </nav>

  <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="sidebar">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">Menu</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <ul class="nav flex-column">
        <li class="nav-item"><a href="/files" class="nav-link"><i class="bi bi-folder-fill me-2"></i> Files</a></li>
        <li class="nav-item"><a href="/settings" class="nav-link"><i class="bi bi-gear-fill me-2"></i> Settings</a></li>
      </ul>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row flex-nowrap">
      <!-- Sidebar for desktop -->
      <div class="col-auto col-md-3 col-xl-2 bg-light d-none d-md-block">
        <div class="d-flex flex-column px-3 pt-2">
          <h5 class="fw-bold text-dark">📁 File Manager</h5>
          <ul class="nav nav-pills flex-column">
            <li class="nav-item"><a href="/files" class="nav-link text-dark"><i class="bi bi-folder-fill me-2"></i> Files</a></li>
            <li class="nav-item"><a href="/settings" class="nav-link text-dark"><i class="bi bi-gear-fill me-2"></i> Settings</a></li>
          </ul>
        </div>
      </div>

      <!-- Main content -->
      <div class="col p-3">
        <?php
        $viewFile = __DIR__ . '/' . $view . '.php';
        if (file_exists($viewFile)) {
          include $viewFile;
        } else {
          echo "<h1>404 Not Found</h1>";
        }
        ?>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
