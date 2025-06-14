<?php
    $root = __DIR__;
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = $root . $uri;
    if (is_file($file)) {
        return false;
    }
    require_once $root . '/index.php';
?>