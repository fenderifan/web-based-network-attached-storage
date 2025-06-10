<?php
// config.php

define('CONFIG_PATH', __DIR__ . '/config.json');

/**
 * Loads settings from config.json, merging with defaults.
 *
 * @return array The merged settings.
 */
function load_settings() {
    // Default settings ensure the app works on first run
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
        return $defaults; // Return defaults if JSON is corrupt
    }

    // Merge defaults with current to ensure all keys are set
    return array_merge($defaults, $current);
}

/**
 * Saves the provided settings array to config.json.
 *
 * @param array $settings The settings array to save.
 * @return bool True on success, false on failure.
 */
function save_settings($settings) {
    // First, ensure the file exists and is writable.
    // If it doesn't exist, this will try to create it.
    if (!file_exists(CONFIG_PATH)) {
        // Attempt to create the file by opening it in write mode.
        $handle = @fopen(CONFIG_PATH, 'w');
        if ($handle === false) {
            // Failed to create the file, likely a directory permission issue.
            return false;
        }
        fclose($handle);
    }

    // Ensure the file is writable by the script's user.
    // This is a good check even if the file exists.
    if (!is_writable(CONFIG_PATH)) {
        // Attempt to make it writable. This may fail on some systems
        // but is worth trying.
        @chmod(CONFIG_PATH, 0664);
    }

    // Now, attempt to write the content.
    $result = @file_put_contents(CONFIG_PATH, json_encode($settings, JSON_PRETTY_PRINT));

    return $result !== false;
}

?>