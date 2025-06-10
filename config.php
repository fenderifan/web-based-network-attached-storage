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
    return file_put_contents(CONFIG_PATH, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

?>