<?php
/**
 * PHP Temporary Directory Fix Script
 * 
 * This script diagnoses and fixes issues with PHP's temporary directory
 * that can cause errors like "The file does not exist or is not readable"
 */

echo "PHP Temporary Directory Fix Tool\n";
echo "===============================\n\n";

// 1. Check current PHP configuration
echo "Current PHP Configuration:\n";
echo "- PHP Version: " . PHP_VERSION . "\n";
echo "- Temp directory (sys_get_temp_dir): " . sys_get_temp_dir() . "\n";
echo "- Upload tmp directory (upload_tmp_dir): " . ini_get('upload_tmp_dir') . "\n";
echo "- File uploads enabled: " . (ini_get('file_uploads') ? 'Yes' : 'No') . "\n";
echo "- Upload max filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- Post max size: " . ini_get('post_max_size') . "\n\n";

// 2. Check if temp directories exist and are writable
$sysTemp = sys_get_temp_dir();
$uploadTemp = ini_get('upload_tmp_dir') ?: $sysTemp;

echo "Checking temporary directories:\n";

// Check system temp directory
if (is_dir($sysTemp)) {
    echo "✅ System temp directory exists: $sysTemp\n";
    
    if (is_writable($sysTemp)) {
        echo "✅ System temp directory is writable\n";
    } else {
        echo "❌ System temp directory is NOT writable\n";
        echo "   Suggested fix: sudo chmod 1777 $sysTemp\n";
    }
} else {
    echo "❌ System temp directory does not exist: $sysTemp\n";
    echo "   Suggested fix: sudo mkdir -p $sysTemp && sudo chmod 1777 $sysTemp\n";
}

// Check upload temp directory if specifically set
if ($uploadTemp !== $sysTemp) {
    if (is_dir($uploadTemp)) {
        echo "✅ Upload temp directory exists: $uploadTemp\n";
        
        if (is_writable($uploadTemp)) {
            echo "✅ Upload temp directory is writable\n";
        } else {
            echo "❌ Upload temp directory is NOT writable\n";
            echo "   Suggested fix: sudo chmod 1777 $uploadTemp\n";
        }
    } else {
        echo "❌ Upload temp directory does not exist: $uploadTemp\n";
        echo "   Suggested fix: sudo mkdir -p $uploadTemp && sudo chmod 1777 $uploadTemp\n";
    }
}

// 3. Try to create a test temporary file
echo "\nTesting temporary file creation:\n";

try {
    $testFile = tempnam(sys_get_temp_dir(), 'test');
    if ($testFile) {
        echo "✅ Successfully created test file: $testFile\n";
        
        // Test writing to the file
        if (file_put_contents($testFile, "Test content")) {
            echo "✅ Successfully wrote to test file\n";
        } else {
            echo "❌ Failed to write to test file\n";
        }
        
        // Clean up
        if (unlink($testFile)) {
            echo "✅ Successfully removed test file\n";
        } else {
            echo "❌ Failed to remove test file\n";
        }
    } else {
        echo "❌ Failed to create test file\n";
    }
} catch (Exception $e) {
    echo "❌ Error during test file creation: " . $e->getMessage() . "\n";
}

// 4. Create a custom upload directory in the project
$projectDir = dirname(__DIR__);
$customUploadDir = $projectDir . '/var/uploads/temp';

echo "\nSetting up custom upload directory:\n";

if (!is_dir($customUploadDir)) {
    if (mkdir($customUploadDir, 0777, true)) {
        echo "✅ Created custom upload directory: $customUploadDir\n";
    } else {
        echo "❌ Failed to create custom upload directory\n";
    }
} else {
    echo "✅ Custom upload directory already exists: $customUploadDir\n";
}

// Make sure the directory is writable
if (is_writable($customUploadDir)) {
    echo "✅ Custom upload directory is writable\n";
} else {
    chmod($customUploadDir, 0777);
    if (is_writable($customUploadDir)) {
        echo "✅ Made custom upload directory writable\n";
    } else {
        echo "❌ Custom upload directory is NOT writable\n";
        echo "   Suggested fix: sudo chmod -R 777 $customUploadDir\n";
    }
}

// 5. Create a PHP configuration file
$phpIniPath = $projectDir . '/php.ini';

echo "\nCreating custom PHP configuration file:\n";

$phpIniContent = <<<PHP_INI
; Custom PHP configuration for Quai Antique
file_uploads = On
upload_tmp_dir = "$customUploadDir"
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
PHP_INI;

if (file_put_contents($phpIniPath, $phpIniContent)) {
    echo "✅ Created custom PHP configuration at: $phpIniPath\n";
    echo "   To use this configuration with PHP CLI: php -c $phpIniPath your_script.php\n";
    echo "   To use with your web server, include the path in your server configuration\n";
} else {
    echo "❌ Failed to create custom PHP configuration\n";
}

// 6. Provide additional advice
echo "\nAdditional advice:\n";
echo "- Restart your web server after making changes:\n";
echo "  sudo service apache2 restart # for Apache\n";
echo "  sudo service nginx restart # for Nginx\n";
echo "  sudo service php8.3-fpm restart # if using PHP-FPM\n";
echo "- For Symfony CLI, try: APP_RUNTIME_ENV=php php -c $phpIniPath bin/console server:start\n";
echo "- Check ownership of temp directories: they should be owned by www-data or your web server user\n";
echo "- If using Docker, make sure volumes are properly configured\n";

echo "\nFix completed!\n";
