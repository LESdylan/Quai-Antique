<?php
// Built-in server router for Symfony
if (php_sapi_name() !== 'cli-server') {
    die("This script is only meant to be run with PHP's built-in web server");
}

// Static file check
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__ . '/public'; // or 'web' for older Symfony versions
$file = $publicDir . $uri;

if (is_file($file)) {
    // Serve the requested file as-is
    return false;
}

// Handle front controller
$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';
require $publicDir . '/index.php';
