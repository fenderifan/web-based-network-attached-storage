<?php
// settings.php
require_once __DIR__ . '/../config.php';

$currentSettings = load_settings();

// This block now handles the background fetch request from JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentSettings['theme'] = $_POST['theme'] ?? 'light';
    $currentSettings['default_sort'] = $_POST['default_sort'] ?? 'name_asc';
    $currentSettings['show_hidden_files'] = isset($_POST['show_hidden_files']);
    $currentSettings['type_grouping'] = isset($_POST['type_grouping']); // Added for Type Grouping
    $currentSettings['timezone'] = $_POST['timezone'] ?? 'Asia/Jakarta'; // Added timezone setting

    if (save_settings($currentSettings)) {
        // Send a success response for the fetch request
        http_response_code(200);
        echo "Settings saved successfully!";
    } else {
        http_response_code(500);
        echo "Error: Could not write to config.json.";
    }
    exit; // Stop further execution for fetch requests
}

/**
 * Formats bytes into a human-readable string.
 * @param int $bytes
 * @return string
 */
function format_bytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 0) {
        return $bytes . ' B';
    } else {
        return '0 B';
    }
}

// Get disk space info
$diskFree = disk_free_space(__DIR__);
$diskTotal = disk_total_space(__DIR__);
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = ($diskUsed / $diskTotal) * 100;

?>

<div class="container-fluid p-4">
    <h3 class="mb-4">Settings</h3>

    <div id="alert-container"></div>

    <div class="card">
        <div class="card-header">Display Settings</div>
        <div class="card-body">
            <form id="settingsForm" method="POST">
                <div class="mb-3">
                    <label for="themeSelect" class="form-label">Theme</label>
                    <select id="themeSelect" name="theme" class="form-select">
                        <option value="light" <?= ($currentSettings['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light Mode</option>
                        <option value="dark" <?= ($currentSettings['theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark Mode</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="timezoneSelect" class="form-label">Timezone</label>
                    <select id="timezoneSelect" name="timezone" class="form-select">
                        <?php
                        $selected_timezone = $currentSettings['timezone'] ?? 'Asia/Jakarta';
                        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                        foreach ($timezones as $timezone) {
                            $selected = ($timezone === $selected_timezone) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($timezone)."\" $selected>".htmlspecialchars($timezone)."</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="sortSelect" class="form-label">Default Sort Order</label>
                    <select id="sortSelect" name="default_sort" class="form-select">
                        <option value="name_asc" <?= ($currentSettings['default_sort'] ?? 'name_asc') === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= ($currentSettings['default_sort'] ?? 'name_asc') === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="date_desc" <?= ($currentSettings['default_sort'] ?? 'name_asc') === 'date_desc' ? 'selected' : '' ?>>Date (Newest First)</option>
                        <option value="date_asc" <?= ($currentSettings['default_sort'] ?? 'name_asc') === 'date_asc' ? 'selected' : '' ?>>Date (Oldest First)</option>
                        <option value="size_desc" <?= ($currentSettings['default_sort'] ?? 'name_asc') === 'size_desc' ? 'selected' : '' ?>>Size (Largest First)</option>
                        <option value="size_asc" <?= ($currentSettings['default_sort'] ?? 'name_asc') === 'size_asc' ? 'selected' : '' ?>>Size (Smallest First)</option>
                    </select>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="showHiddenFiles" name="show_hidden_files" <?= ($currentSettings['show_hidden_files'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="showHiddenFiles">Show Hidden Files (.dotfiles)</label>
                </div>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="typeGrouping" name="type_grouping" <?= ($currentSettings['type_grouping'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="typeGrouping">Group by File Type</label>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">System Information</div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Server Software:</span>
                    <strong><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE']) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>PHP Version:</span>
                    <strong><?= htmlspecialchars(phpversion()) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Max Execution Time:</span>
                    <strong><?= ini_get('max_execution_time') ?>s</strong>
                </li>
                 <li class="list-group-item d-flex justify-content-between">
                    <span>Memory Limit:</span>
                    <strong><?= ini_get('memory_limit') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Max Upload Size:</span>
                    <strong><?= ini_get('upload_max_filesize') ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>File Uploads:</span>
                    <strong><?= ini_get('file_uploads') ? 'Enabled' : 'Disabled' ?></strong>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">Disk Usage</div>
        <div class="card-body">
            <div class="progress" style="height: 25px;">
                <div class="progress-bar" role="progressbar" style="width: <?= $diskUsagePercent ?>%;" aria-valuenow="<?= $diskUsagePercent ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= number_format($diskUsagePercent, 1) ?>% Used
                </div>
            </div>
            <div class="d-flex justify-content-between mt-2 text-muted small">
                <span>Used: <strong><?= format_bytes($diskUsed) ?></strong></span>
                <span>Free: <strong><?= format_bytes($diskFree) ?></strong></span>
                <span>Total: <strong><?= format_bytes($diskTotal) ?></strong></span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const newTheme = formData.get('theme');
            const alertContainer = document.getElementById('alert-container');

            fetch('/views/settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                }
                throw new Error('Failed to save settings.');
            })
            .then(message => {
                // 1. Instantly change the theme on the page
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                
                // --- START: CORRECTED LOGIC ---

                // 2. Define all theme-related elements and classes
                const isDark = newTheme === 'dark';
                const desktopSidebar = document.querySelector('.d-none.d-md-block.position-sticky');
                const mobileSidebar = document.querySelector('.offcanvas#sidebar');
                const mobileNavbar = document.querySelector('.navbar.d-md-none');
                
                const sidebarClassToAdd = isDark ? 'sidebar-custom-dark' : 'bg-light';
                const navbarClassToAdd = isDark ? 'bg-dark' : 'bg-light';
                const textClassToAdd = isDark ? 'text-light' : 'text-dark';

                // 3. Update background classes by removing all possibilities and adding the correct one
                [desktopSidebar, mobileSidebar, mobileNavbar].forEach(el => {
                    if (!el) return; // Skip if element doesn't exist
                    el.classList.remove('sidebar-custom-dark', 'bg-light', 'bg-dark');
                    if (el.matches('.navbar')) {
                        el.classList.add(navbarClassToAdd);
                    } else {
                        el.classList.add(sidebarClassToAdd);
                    }
                });

                // 4. Update text classes on all relevant links and headers
                const textElements = document.querySelectorAll('.d-md-block .nav-link, .d-md-block h5, .offcanvas .nav-link, .offcanvas h5');
                textElements.forEach(el => {
                    el.classList.remove('text-light', 'text-dark');
                    el.classList.add(textClassToAdd);
                });
                
                // --- END: CORRECTED LOGIC ---

                // 5. Show a success message
                alertContainer.innerHTML = `<div class="alert alert-success">${message}</div>`;
                setTimeout(() => alertContainer.innerHTML = '', 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
                setTimeout(() => alertContainer.innerHTML = '', 3000);
            });
        });
    }
});
</script>