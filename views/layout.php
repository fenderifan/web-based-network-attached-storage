<?php
$subPath = $subPath ?? '/';
?>

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
          <h5 class="fw-bold text-dark"><i class="bi bi-folder-fill text-warning fs-1"></i> File Manager</h5>
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
    <!-- Drag Overlay -->
    <div id="uploadOverlay"class="position-fixed top-0 start-0 w-100 h-100 d-none"style="z-index: 1050; backdrop-filter: blur(6px); background-color: rgba(255,255,255,0.6); display: flex; align-items: center; justify-content: center;">
      <div class="text-center">
        <i class="bi bi-cloud-upload-fill fs-1 text-primary" style="font-size: 4rem;"></i>
        <p class="fw-semibold mt-2 fs-5">Drop files to upload</p>
      </div>
    </div>
    <div id="uploadStatus" class="position-fixed bottom-0 end-0 m-3 p-2 bg-white rounded shadow" style="z-index: 1100; max-width: 300px; overflow-y: auto; max-height: 50vh;">
    </div>
<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="renameForm">
        <div class="modal-header">
          <h5 class="modal-title">Rename File</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="oldPath" id="oldPathInput">
          <div class="mb-3">
            <label for="newNameInput" class="form-label">New name</label>
            <input type="text" class="form-control" id="newNameInput" name="newName" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Rename</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (strpos($_SERVER['REQUEST_URI'], '/files') === 0): ?>
<script>
let previousHTML = '';
let isEditing = false;

document.addEventListener('focusin', e => {
  if (e.target.closest('.modal')) isEditing = true;
});

document.addEventListener('focusout', e => {
  if (e.target.closest('.modal')) isEditing = false;
});

function refreshFileList() {
  if (isEditing) return;
  fetch(window.location.href)
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newList = doc.querySelector('.list-group');
      const currentList = document.querySelector('.list-group');

      if (newList && newList.innerHTML !== previousHTML) {
        previousHTML = newList.innerHTML;

        // Replace list without wiping modals
        currentList.innerHTML = newList.innerHTML;

        // Re-bind buttons (rename, delete, etc.)
        bindFileManagerButtons();
      }
    });
}

setInterval(refreshFileList, 10000);


const overlay = document.getElementById('uploadOverlay');
const statusDiv = document.getElementById('uploadStatus');

document.addEventListener('dragover', e => {
  e.preventDefault();
  overlay.classList.remove('d-none');
});

document.addEventListener('dragleave', e => {
  if (e.clientY <= 0 || e.clientX <= 0) {
    overlay.classList.add('d-none');
  }
});

document.addEventListener('drop', e => {
  e.preventDefault();
  overlay.classList.add('d-none');

  const files = Array.from(e.dataTransfer.files);
  if (files.length > 0) {
    files.forEach(uploadFileWithProgress);
  }
});

document.querySelectorAll('.rename-button').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    const path = btn.dataset.path;
    const name = btn.dataset.name;

    document.getElementById('oldPathInput').value = path;
    document.getElementById('newNameInput').value = name;

    const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
    renameModal.show();
  });
});

document.getElementById('renameForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch('/update.php', {
    method: 'POST',
    body: formData
  })
  .then(res => {
    if (res.ok) {
      location.reload();
    } else {
      return res.text().then(msg => alert("Rename failed: " + msg));
    }
  })
  .catch(err => alert("Error: " + err));
});


function uploadFileWithProgress(file) {
  const formData = new FormData();
  formData.append('fileToUpload', file);
  formData.append('targetFolder', "<?= htmlspecialchars($subPath) ?>");

  const startTime = performance.now();

  const container = document.createElement('div');
  container.className = 'mb-2';

  container.innerHTML = `
    <div class="fw-semibold small">${file.name}</div>
    <div class="progress mb-1" style="height: 20px;">
      <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
    </div>
    <div class="small text-muted">Starting...</div>
  `;

  const progressBar = container.querySelector('.progress-bar');
  const statusText = container.querySelector('.text-muted');
  statusDiv.appendChild(container);

  const xhr = new XMLHttpRequest();

  xhr.upload.addEventListener('progress', e => {
    if (e.lengthComputable) {
      const percent = (e.loaded / e.total) * 100;
      progressBar.style.width = `${percent.toFixed(1)}%`;
      statusText.textContent = `Uploading... ${Math.round(percent)}%`;
    }
  });

  xhr.onload = () => {
  const duration = ((performance.now() - startTime) / 1000).toFixed(2);
  if (xhr.status === 200) {
    progressBar.classList.remove('progress-bar-animated');
    progressBar.style.width = '100%';
    statusText.textContent = `Done in ${duration}s`;

    // ✅ Refresh file list immediately after successful upload
    refreshFileList();
    setTimeout(() => {
      container.remove();
    }, 5000);
  } else {
    progressBar.classList.replace('bg-success', 'bg-danger');
    statusText.textContent = `Failed: ${xhr.responseText}`;
  }
};


  xhr.onerror = () => {
    progressBar.classList.replace('bg-success', 'bg-danger');
    statusText.textContent = 'Upload error.';
  };

  xhr.open('POST', '/upload.php');
  xhr.send(formData);
}

</script>

<?php endif; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
