<?php
	define('CONFIG_PATH', __DIR__ . '/config.json');
	function load_settings() {
			$defaults = [
					'show_hidden_files' => false,
					'default_sort' => 'name_asc',
					'theme' => 'light',
			];
			if (!file_exists(CONFIG_PATH)) {
					return $defaults;
			}
			$current = json_decode(file_get_contents(CONFIG_PATH), true);
			if (!is_array($current)) {
					return $defaults;
			}
			return array_merge($defaults, $current);
	}
	function save_settings($settings) {
			if (!file_exists(CONFIG_PATH)) {
					$handle = @fopen(CONFIG_PATH, 'w');
					if ($handle === false) {
							return false;
					}
					fclose($handle);
			}
			if (!is_writable(CONFIG_PATH)) {
					@chmod(CONFIG_PATH, 0664);
			}
			$result = @file_put_contents(CONFIG_PATH, json_encode($settings, JSON_PRETTY_PRINT));
			return $result !== false;
	}
?>