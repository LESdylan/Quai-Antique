<?php

/**
 * MySQL extension check
 * This file verifies that MySQL extensions are properly installed
 */

// Check for required MySQL extensions
$hasMysqlnd = extension_loaded('mysqlnd');
$hasPdoMysql = extension_loaded('pdo_mysql');

// If MySQL extensions are missing, display a helpful error message
if (!$hasMysqlnd || !$hasPdoMysql) {
    $errorMessage = "\n" .
        "=============================================================\n" .
        "ERROR: MYSQL EXTENSIONS NOT LOADED\n" .
        "=============================================================\n";
        
    if (!$hasMysqlnd) {
        $errorMessage .= "✗ The 'mysqlnd' extension is not loaded.\n";
    }
    
    if (!$hasPdoMysql) {
        $errorMessage .= "✗ The 'pdo_mysql' extension is not loaded.\n";
    }
    
    $errorMessage .= "\n" .
        "Please run the MySQL setup script to install the required extensions:\n" .
        "    sudo bash bin/mysql-setup.sh\n\n" .
        "Or manually install the PHP MySQL extensions for your system.\n" .
        "=============================================================\n";
    
    echo $errorMessage;
}

return [
    'mysql_available' => ($hasMysqlnd && $hasPdoMysql),
];
