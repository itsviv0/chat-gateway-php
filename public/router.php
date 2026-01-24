<?php
// Router script for PHP built-in web server
// This ensures all requests go through index.php unless they're actual files

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    
    // If file exists and is not a directory, serve it
    if (is_file($file)) {
        return false;
    }
    
    // Otherwise, route through index.php
    require_once __DIR__ . '/index.php';
}
