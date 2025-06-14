<?php
	require_once __DIR__ . '/../config.php';
	require_once __DIR__ . '/../logging.php';
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$oldSettings = load_settings();
		$newSettings = [
			'theme' => isset($_POST['theme']) && $_POST['theme'] === 'dark' ? 'dark' : 'light',
			'timezone' => isset($_POST['timezone']) && in_array($_POST['timezone'], DateTimeZone::listIdentifiers()) ? $_POST['timezone'] : ($oldSettings['timezone'] ?? 'Asia/Jakarta'),
			'default_sort' => $_POST['default_sort'] ?? ($oldSettings['default_sort'] ?? 'name_asc'),
			'show_hidden_files' => isset($_POST['show_hidden_files']),
			'type_grouping' => isset($_POST['type_grouping']),
			'chunk_size' => $_POST['chunk_size'] ?? ($oldSettings['chunk_size'] ?? '5')
		];
		foreach ($newSettings as $key => $newValue) {
			$oldValue = $oldSettings[$key] ?? null;
			$displayOld = is_bool($oldValue) ? ($oldValue ? 'true' : 'false') : $oldValue;
			$displayNew = is_bool($newValue) ? ($newValue ? 'true' : 'false') : $newValue;
			if ($oldValue != $newValue) {
				write_log("Setting changed: '{$key}' from '{$displayOld}' to '{$displayNew}'");
			}
		}
		if (save_settings($newSettings)) {
			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
		} else {
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to write to config file. Check permissions.']);
		}
		exit;
	}
	$currentSettings = load_settings();
	$diskFree = @disk_free_space(__DIR__);
	$diskTotal = @disk_total_space(__DIR__);
	$diskUsed = $diskTotal > 0 ? $diskTotal - $diskFree : 0;
	$diskUsagePercent = $diskTotal > 0 ? ($diskUsed / $diskTotal) * 100 : 0;
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
				<div class="mb-3">
					<label for="chunkSizeSelect" class="form-label">Upload Chunk Size (MB)</label>
					<select id="chunkSizeSelect" name="chunk_size" class="form-select">
						<?php
							$current_chunk_size = $currentSettings['chunk_size'] ?? '5';
							for ($i = 1; $i <= 20; $i++) {
								$selected = ($i == $current_chunk_size) ? 'selected' : '';
								echo "<option value=\"{$i}\" {$selected}>{$i} MB</option>";
							}
						?>
					</select>
					<div class="form-text">Set the size of each chunk for large file uploads (1-20 MB). Larger sizes can be faster but use more memory.</div>
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
				const alertContainer = document.getElementById('alert-container');
				fetch('/settings', {
					method: 'POST',
					body: formData
				}).then(response => response.json()).then(data => {
					if (data.success) {
						const alertContainer = document.getElementById('alert-container');
						alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
						setTimeout(() => alertContainer.innerHTML = '', 3000);
						const newTheme = formData.get('theme');
						const isDark = newTheme === 'dark';
						document.documentElement.setAttribute('data-bs-theme', newTheme);
						const navbar = document.querySelector('.navbar.d-md-none');
						if (navbar) {
							navbar.classList.toggle('bg-dark', isDark);
							navbar.classList.toggle('bg-light', !isDark);
						}
						const sidebars = document.querySelectorAll('.offcanvas, .d-none.d-md-block.position-sticky');
						sidebars.forEach(sidebar => {
							sidebar.classList.toggle('sidebar-custom-dark', isDark);
							sidebar.classList.toggle('bg-light', !isDark);
						});
						const sidebarText = document.querySelectorAll('.offcanvas .nav-link, .position-sticky .nav-link, .offcanvas-title, .position-sticky h5');
						sidebarText.forEach(el => {
							el.classList.toggle('text-light', isDark);
							el.classList.toggle('text-dark', !isDark);
						});
					} else {
						throw new Error(data.message);
					}
				}).catch(error => {
					const alertContainer = document.getElementById('alert-container');
					console.error('Error:', error);
					alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
					setTimeout(() => alertContainer.innerHTML = '', 3000);
				});
			});
		}
	});
</script>
