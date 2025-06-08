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
  overflow-x: hidden;
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
    .col {
  min-width: 0; /* allows flex children to shrink */
  overflow-x: auto; /* if really needed */

  .truncate-custom {
  max-width: calc(100vw - 100px);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (min-width: 768px) {
  /* Bootstrap md breakpoint is 768px */
  .truncate-custom {
    max-width: calc(100vw - 750px);
  }
}

}
@media (max-width: 767.98px) {
  #uploadContainer {
    width: 100% !important;      /* Override inline style to be full width */
    margin: 0 !important;         /* Override .m-3 class */
    left: 0 !important;           /* Ensure it starts from the left edge */
    right: 0 !important;          /* Ensure it goes to the right edge */
    border-radius: 0 !important;  /* Remove rounded corners */
    border-left: none !important; /* Remove side borders to blend */
    border-right: none !important;
  }
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
    <div class="row flex-wrap">
      <!-- Sidebar for desktop -->
      <div class="d-none d-md-block bg-light position-sticky top-0"
     style="height: 100vh; width: 250px;">
  <div class="d-flex flex-column px-3 pt-2">
    <h5 class="fw-bold text-dark">
      <i class="bi bi-folder-fill text-warning"></i> File Manager
    </h5>
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

    <!-- Upload Status -->
<div id="uploadContainer" class="upload-container position-fixed bottom-0 end-0 m-3 shadow rounded border" style="width: 380px; z-index: 1050; display: none;">
    <div class="upload-header d-flex justify-content-between align-items-center p-2 border-bottom bg-white">
        <h6 class="mb-0 ms-2">Uploading... (<span id="uploadCount">0</span>)</h6>
        <div>
            <button type="button" id="toggleUploads" class="btn btn-sm btn-icon">
                <i class="bi-chevron-up"></i>
            </button>
        </div>
    </div>
    <div id="uploadBody" class="upload-body p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
        </div>
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
        <button class="btn btn-outline-primary" onclick="document.getElementById('mobileUploadInput').click()">
          <i class="bi bi-upload me-2"></i> Upload File
        </button>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newFolderModal">
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
document.addEventListener('DOMContentLoaded', () => {

    // --- Start: New Uploader Variables and Logic ---
    const uploadContainer = document.getElementById('uploadContainer');
    const uploadBody = document.getElementById('uploadBody');
    const uploadHeader = document.getElementById('uploadHeader');
    const toggleBtn = document.getElementById('toggleUploads');
    const closeBtn = document.getElementById('closeUploads');
    const uploadCountSpan = document.getElementById('uploadCount');
    const toggleIcon = toggleBtn.querySelector('i');
    const fileListContainer = document.querySelector('.list-group');
    let activeUploads = 0;

    function updateUploadCount() {
        if (uploadCountSpan) {
            uploadCountSpan.textContent = activeUploads;
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            const isCollapsed = uploadBody.style.display === 'none';
            uploadBody.style.display = isCollapsed ? 'block' : 'none';
            toggleIcon.classList.toggle('bi-chevron-up', isCollapsed);
            toggleIcon.classList.toggle('bi-chevron-down', !isCollapsed);
        });
    }

    if (uploadHeader) {
        uploadHeader.addEventListener('click', () => {
            toggleBtn.click();
        });
    }
    // --- End: New Uploader Logic ---


    let previousHTML = '';
    let isEditing = false;

    document.addEventListener('focusin', e => {
        if (e.target.closest('.modal')) isEditing = true;
    });

    document.addEventListener('focusout', e => {
        if (e.target.closest('.modal')) isEditing = false;
    });

    function refreshFileList(force = false) {
    if (isEditing && !force) return; // This line is changed
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newList = doc.querySelector('.list-group');
            const currentList = document.querySelector('.list-group');

            if (newList && currentList && newList.innerHTML !== previousHTML) {
                previousHTML = newList.innerHTML;
                currentList.innerHTML = newList.innerHTML;
                bindFileManagerButtons();
            }
        });
}
    setInterval(refreshFileList, 10000);

    const overlay = document.getElementById('uploadOverlay');
    document.addEventListener('dragover', e => {
        e.preventDefault();
        overlay.classList.remove('d-none');
    });

    document.addEventListener('dragleave', e => {
        if (e.relatedTarget === null || e.relatedTarget.closest('#uploadOverlay') === null) {
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
    
    // MODIFIED uploadFileWithProgress function
    function uploadFileWithProgress(file) {
    activeUploads++;
    updateUploadCount();
    uploadContainer.style.display = 'block';
    if (window.innerWidth < 768 && fileListContainer) {
        fileListContainer.style.paddingBottom = '35px';
    }
    const chunkSize = 5 * 1024 * 1024; // 5MB chunks
    const totalChunks = Math.ceil(file.size / chunkSize);
    let chunkNumber = 0;

    const fileExt = (file.name.split('.').pop() || 'FILE').toUpperCase().substring(0, 4);

    const container = document.createElement('div');
    container.className = 'p-2 mb-2 border rounded bg-white';
    container.innerHTML = `
      <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-2">
          <div class="d-flex align-items-center justify-content-center bg-body-tertiary text-secondary fw-bold rounded" style="width: 40px; height: 40px; font-size: 0.8rem;">
            ${fileExt}
          </div>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="fw-semibold small" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${file.name}">
            ${file.name}
          </div>
          <div class="progress mt-1" style="height: 5px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
          </div>
          <div class="d-flex justify-content-between small text-muted mt-1">
            <div class="upload-status" style="font-size: 0.75rem;">Starting...</div>
            <div class="upload-meta text-end" style="font-size: 0.75rem;"></div>
          </div>
        </div>
      </div>
    `;
    uploadBody.appendChild(container);

    const progressBar = container.querySelector('.progress-bar');
    const statusText = container.querySelector('.upload-status');
    const metaText = container.querySelector('.upload-meta');

    function uploadNextChunk() {
        if (chunkNumber >= totalChunks) {
            return; // All chunks are uploaded
        }

        const start = chunkNumber * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);
        
        const formData = new FormData();
        formData.append('fileToUpload', chunk);
        formData.append('chunkNumber', chunkNumber);
        formData.append('totalChunks', totalChunks);
        formData.append('fileName', file.name);
        formData.append('targetFolder', "<?= htmlspecialchars($subPath) ?>");
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/upload.php', true);
        
        let chunkStartTime = performance.now();

        // Add progress listener for real-time speed and ETA
        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                // Calculate overall file progress
                const totalBytesUploaded = (chunkNumber * chunkSize) + e.loaded;
                const percentComplete = (totalBytesUploaded / file.size) * 100;
                progressBar.style.width = `${percentComplete}%`;

                // Calculate speed based on current chunk's progress
                const timeElapsed = (performance.now() - chunkStartTime) / 1000; // in seconds
                if (timeElapsed > 0.2) { // Update only after a short delay to get a stable reading
                    const speedBps = e.loaded / timeElapsed; // Bytes per second
                    const speedMBps = speedBps / (1024 * 1024);
                    
                    // Calculate ETA based on remaining bytes for the whole file
                    const remainingBytes = file.size - totalBytesUploaded;
                    const etaSeconds = speedBps > 0 ? Math.round(remainingBytes / speedBps) : 0;
                    
                    statusText.textContent = `${speedMBps.toFixed(2)} MB/s`;
                    metaText.textContent = `${etaSeconds}s left`;
                }
            }
        });

        xhr.onload = () => {
            if (xhr.status === 200) {
                if (chunkNumber === totalChunks - 1) {
                    // Last chunk was successful, finalize the upload UI
                    progressBar.style.width = '100%';
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.replace('bg-primary', 'bg-success');
                    statusText.textContent = 'Completed!';
                    metaText.textContent = `(${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                    refreshFileList(true);

                    setTimeout(() => {
                        container.remove();
                        activeUploads--;
                        updateUploadCount();
                        if (activeUploads === 0) {
                          if (fileListContainer) {
                              fileListContainer.style.paddingBottom = '';
                          }
                          uploadContainer.style.display = 'none';
                        }
                    }, 5000);
                } else {
                    // Upload the next chunk
                    chunkNumber++;
                    uploadNextChunk();
                }
            } else {
                // Handle server error for a chunk
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.replace('bg-primary', 'bg-danger');
                statusText.textContent = 'Upload failed';
                metaText.textContent = xhr.responseText || 'Server Error';
            }
        };

        xhr.onerror = () => {
            // Handle network errors
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.replace('bg-primary', 'bg-danger');
            statusText.textContent = 'Network Error';
        };
        
        xhr.send(formData);
    }
    
    // Start the upload chain
    uploadNextChunk();
}

    function triggerMobileUpload() {
        document.getElementById('mobileUploadInput').click();
    }

    document.getElementById('mobileUploadInput').addEventListener('change', function() {
        const files = Array.from(this.files);
        if (files.length > 0) {
            files.forEach(uploadFileWithProgress);
        }
        this.value = '';
    });
    
    // --- All your other functions (create folder, modals, bind buttons, etc.) ---
    
    document.getElementById('newFolderForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const folderName = document.getElementById('folderName').value.trim();
      if (!folderName) return;

      const formData = new FormData();
      formData.append('folderName', folderName);
      formData.append('targetFolder', "<?= htmlspecialchars($subPath) ?>");

      fetch('/create.php', { method: 'POST', body: formData })
        .then(res => res.ok ? refreshFileList(true) : res.text().then(alert))
        .catch(err => alert("Error: " + err));
      
      const modal = bootstrap.Modal.getInstance(document.getElementById('newFolderModal'));
      modal.hide();
    });

    const previewModal = document.getElementById('previewModal');
    previewModal.addEventListener('hidden.bs.modal', function () {
      document.getElementById('previewModalContent').innerHTML = '';
    });

    function bindFileManagerButtons() {
        document.querySelectorAll('.rename-button').forEach(btn => {
          btn.addEventListener('click', e => {
              e.preventDefault();
              const path = btn.dataset.path;
              const name = btn.dataset.name;
              const isDir = btn.closest('.list-group-item').querySelector('.bi-folder-fill') !== null;
              
              document.getElementById('oldPathInput').value = path;
              const newBaseNameInput = document.getElementById('newBaseNameInput');
              const newExtensionInput = document.getElementById('newExtensionInput');
              const extensionGroup = newExtensionInput.closest('.mb-3');

              if (isDir) {
                  newBaseNameInput.value = name;
                  newExtensionInput.value = '';
                  extensionGroup.style.display = 'none';
              } else {
                  const lastDot = name.lastIndexOf('.');
                  if (lastDot !== -1 && lastDot !== 0) {
                      newBaseNameInput.value = name.substring(0, lastDot);
                      newExtensionInput.value = name.substring(lastDot + 1);
                  } else {
                      newBaseNameInput.value = name;
                      newExtensionInput.value = '';
                  }
                  extensionGroup.style.display = 'block';
              }

              const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
              renameModal.show();
          });
      });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const filePath = button.getAttribute('data-path');
                if (!filePath) return alert("No file path provided.");
                if (!confirm(`Are you sure you want to delete this item?\n\nPath: ${filePath}`)) return;

                try {
                    const response = await fetch('/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ path: filePath })
                    });
                    const resultText = await response.text();
                    if (response.ok) {
                        refreshFileList(true);
                    } else {
                        alert("Delete failed: " + resultText);
                    }
                } catch (err) {
                    alert("An error occurred during deletion.");
                }
            });
        });

        document.querySelectorAll('.preview-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.dataset.preview;
                const modalBody = document.getElementById('previewModalContent');
                modalBody.innerHTML = '<div class="modal-body text-center p-5 text-muted">Loading...</div>';
                fetch(url)
                    .then(res => res.text())
                    .then(html => modalBody.innerHTML = html)
                    .catch(() => modalBody.innerHTML = '<div class="modal-body text-danger text-center p-5">Error loading preview.</div>');
            });
        });

        document.querySelectorAll('.copy-path-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const path = btn.getAttribute('data-path');
                const fullUrl = window.location.origin + path;
                navigator.clipboard.writeText(fullUrl).then(() => alert('Copied URL: ' + fullUrl));
            });
        });
    }

    document.getElementById('renameForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const base = document.getElementById('newBaseNameInput').value.trim();
    const ext = document.getElementById('newExtensionInput').value.trim();
    const isDir = document.getElementById('newExtensionInput').closest('.mb-3').style.display === 'none';

    let newName = base;
    if (!isDir && ext) {
        newName = `${base}.${ext}`;
    }

    const formData = new FormData(this);
    formData.set('newName', newName);

    fetch('/update.php', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (res.ok) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('renameModal'));
                modal.hide();
                refreshFileList(true);
            } else {
                return res.text().then(msg => alert("Rename failed: " + msg));
            }
        })
        .catch(err => alert("Error: " + err));
});

    bindFileManagerButtons();
});
</script>
<?php endif; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
