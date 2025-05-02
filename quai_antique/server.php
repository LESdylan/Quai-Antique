<?php
// Simple router for the PHP built-in server
if (php_sapi_name() === 'cli-server') {
    // Static file?
    $file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
    
    // Redirect to front controller
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require_once 'public/index.php';
} else {
    echo "This script is meant to be run with PHP built-in server";
    exit(1);
}
