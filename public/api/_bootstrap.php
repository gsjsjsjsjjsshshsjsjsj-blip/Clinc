<?php
// Common bootstrap for API endpoints
require_once __DIR__ . '/../../vendor/autoload.php';

// Fallback autoloader if Composer is not used
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

header('Content-Type: application/json; charset=utf-8');
