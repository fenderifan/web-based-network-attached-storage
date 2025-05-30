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

@media (max-width: 767.98px) {
  html, body {
    height: 100vh;
    overflow: hidden;
  }

  .container-fluid {
    overflow-y: auto;
    height: calc(100vh - 56px); /* mobile navbar height */
  }
}

@media (min-width: 768px) {
  body {
    overflow-y: auto; /* allow scroll on desktop */
  }
}
    .file-item {
      cursor: pointer;
    }
    .breadcrumb a {
      text-decoration: none;
    }
    .navbar {
      height: 56px;
      padding: 0;
    }

    .navbar .container-fluid {
      height: 100%;
      align-items: center;
      padding: 0 1rem;
    }

    .navbar-brand {
      font-size: 1.25rem;
      line-height: 1.2;
    }

  </style>
</head>
<body>
  <nav class="navbar navbar-light bg-light d-md-none sticky-top">
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
      <div class="col-auto col-md-3 col-xl-2 bg-light d-none d-md-block position-sticky top-0" style="height: 100vh;">
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
    
    <div id="uploadStatus" class="d-none position-fixed bottom-0 end-0 m-3 p-2 bg-white rounded shadow" style="z-index: 1100; max-width: 300px; overflow-y: auto; max-height: 50vh;">
    </div>
<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="renameForm">
        <div class="modal-header">
          <h5 class="modal-title">Rename File</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="oldPath" id="oldPathInput">
          <div class="mb-3">
            <label for="newBaseNameInput" class="form-label">New name</label>
            <input type="text" class="form-control" id="newBaseNameInput" name="newBaseName" required>
          </div>
          <div class="mb-3">
            <label for="newExtensionInput" class="form-label">Extension</label>
            <input type="text" class="form-control" id="newExtensionInput" name="newExtension">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Rename</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal for mobile actions -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Actions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body d-grid gap-2">
        <button class="btn btn-outline-primary" onclick="triggerMobileUpload()" data-bs-dismiss="modal">
          <i class="bi bi-upload me-2"></i> Upload File
        </button>
        <button class="btn btn-outline-secondary" onclick="showCreateFolderModal()" data-bs-dismiss="modal">
          <i class="bi bi-folder-plus me-2"></i> New Folder
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Hidden file input for upload -->
<input type="file" id="mobileUploadInput" class="d-none" multiple />

<!-- New Folder Modal -->
<div class="modal fade" id="newFolderModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="newFolderForm">
        <div class="modal-header">
          <h5 class="modal-title">Create New Folder</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="folderName" class="form-label">Folder name</label>
            <input type="text" class="form-control" id="folderName" name="folderName" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" id="previewModalContent">
      <div class="modal-body text-center p-5 text-muted">Loading...</div>
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

    const lastDot = name.lastIndexOf('.');
    let base = name;
    let ext = '';
    if (lastDot !== -1 && lastDot !== 0) {
      base = name.substring(0, lastDot);
      ext = name.substring(lastDot + 1);
    }

    document.getElementById('oldPathInput').value = path;
    document.getElementById('newBaseNameInput').value = base;
    document.getElementById('newExtensionInput').value = ext;

    const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
    renameModal.show();
  });
});

document.getElementById('renameForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const base = document.getElementById('newBaseNameInput').value.trim();
  const ext = document.getElementById('newExtensionInput').value.trim();
  const formData = new FormData();

  const newName = ext ? `${base}.${ext}` : base;

  formData.append('oldPath', document.getElementById('oldPathInput').value);
  formData.append('newName', newName);

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

  statusDiv.classList.remove('d-none');

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

    // Refresh file list immediately after successful upload
    refreshFileList();
    setTimeout(() => {
  container.remove();
  if (statusDiv.children.length === 0) {
    statusDiv.classList.add('d-none');
  }
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

document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', async (e) => {
        e.preventDefault();

        const filePath = button.getAttribute('data-path');
        if (!confirm("Are you sure you want to delete this item?")) return;

        try {
            const response = await fetch('/delete.php?path=' + encodeURIComponent(filePath));
            if (response.ok) {
                // Refresh current page to show updated contents
                window.location.reload();
            } else {
                const errorText = await response.text();
                alert("Delete failed: " + errorText);
            }
        } catch (err) {
            console.error(err);
            alert("An error occurred during deletion.");
        }
    });
});

function triggerMobileUpload() {
  document.getElementById('mobileUploadInput').click();
}

document.getElementById('mobileUploadInput').addEventListener('change', function () {
  const files = Array.from(this.files);
  if (files.length > 0) {
    files.forEach(uploadFileWithProgress);
  }
  this.value = ''; // Reset input
});

function showCreateFolderModal() {
  const modal = new bootstrap.Modal(document.getElementById('newFolderModal'));
  modal.show();
}

document.getElementById('newFolderForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const folderName = document.getElementById('folderName').value.trim();
  if (!folderName) return;

  const formData = new FormData();
  formData.append('folderName', folderName);
  formData.append('targetFolder', "<?= htmlspecialchars($subPath) ?>");

  fetch('/create.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.ok ? location.reload() : res.text().then(alert))
    .catch(err => alert("Error: " + err));
});

// Handle click on preview link
document.querySelectorAll('.preview-link').forEach(link => {
  link.addEventListener('click', function (e) {
    const url = this.dataset.preview;
    const modalBody = document.getElementById('previewModalContent');
    modalBody.innerHTML = '<div class="modal-body text-center p-5 text-muted">Loading...</div>';
    fetch(url)
      .then(res => res.text())
      .then(html => modalBody.innerHTML = html)
      .catch(() => modalBody.innerHTML = '<div class="modal-body text-danger text-center p-5">Error loading preview.</div>');
  });
});

// Reset modal content when closed
const previewModal = document.getElementById('previewModal');
previewModal.addEventListener('hidden.bs.modal', function () {
  const modalBody = document.getElementById('previewModalContent');
  modalBody.innerHTML = ''; // Clear the content when modal is closed
});


</script>
<?php endif; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
