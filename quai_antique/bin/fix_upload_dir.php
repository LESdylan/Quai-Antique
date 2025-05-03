<?php
/**
 * Fix PHP Upload Directory Permissions
 *
 * This script creates the necessary upload directories with proper permissions
 * to resolve file upload issues.
 */

// Get project directory
$projectDir = dirname(__DIR__);

// Define directories to create/fix
$directories = [
    // Main uploads directory
    $projectDir . '/public/uploads',
    $projectDir . '/public/uploads/images',
    
    // Temp uploads directory
    $projectDir . '/var/uploads',
    $projectDir . '/var/uploads/temp',
    
    // Cache and log directories (while we're at it)
    $projectDir . '/var/cache',
    $projectDir . '/var/log',
];

echo "PHP Upload Directory Fix Tool\n";
echo "=============================\n\n";

// Show upload_tmp_dir setting 
echo "Current PHP upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'system default') . "\n";
echo "System temporary directory: " . sys_get_temp_dir() . "\n\n";

// Get current user and web server user
$currentUser = get_current_user();
$webServerUser = 'www-data'; // Default for Apache on Ubuntu/Debian

echo "Current user: $currentUser\n";
echo "Web server user: $webServerUser\n\n";

// Create and fix permissions for each directory
foreach ($directories as $dir) {
    echo "Checking directory: $dir\n";
    
    // Create directory if it doesn't exist
    if (!is_dir($dir)) {
        echo "  Creating directory... ";
        if (@mkdir($dir, 0777, true)) {
            echo "SUCCESS\n";
        } else {
            echo "FAILED\n";
            echo "  Error: Could not create directory.\n";
            echo "  Please run: sudo mkdir -p $dir\n";
            continue;
        }
    } else {
        echo "  Directory exists.\n";
    }
    
    // Set directory permissions - more aggressive 0777
    echo "  Setting permissions... ";
    if (@chmod($dir, 0777)) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED\n";
        echo "  Error: Could not set permissions.\n";
        echo "  Please run: sudo chmod -R 777 $dir\n";
    }
    
    // Try to set ownership if running as root
    if (function_exists('posix_getuid') && posix_getuid() === 0) {
        echo "  Setting ownership... ";
        if (@chown($dir, $webServerUser) && @chgrp($dir, $webServerUser)) {
            echo "SUCCESS\n";
        } else {
            echo "FAILED\n";
            echo "  Please run: sudo chown -R $webServerUser:$webServerUser $dir\n";
        }
    } else {
        echo "  Skipping ownership change (not running as root)\n";
        echo "  Consider running: sudo chown -R $webServerUser:$webServerUser $dir\n";
    }
    
    // Check if directory is writable
    echo "  Checking if writable... ";
    if (is_writable($dir)) {
        echo "YES\n";
    } else {
        echo "NO\n";
        echo "  Warning: Directory is not writable!\n";
    }
    
    echo "\n";
}

// Clean up any existing temp files that might be causing issues
$tempDir = $projectDir . '/var/uploads/temp';
echo "Cleaning up old temporary files in $tempDir...\n";
if (is_dir($tempDir)) {
    $tempFiles = glob($tempDir . '/php*');
    foreach ($tempFiles as $file) {
        if (is_file($file)) {
            if (@unlink($file)) {
                echo "  Removed old temp file: " . basename($file) . "\n";
            } else {
                echo "  Failed to remove: " . basename($file) . "\n";
                echo "  Please run: sudo rm $file\n";
            }
        }
    }
}

// Create a test file in the temp directory
echo "\nTesting file creation in temp directory...\n";
$testFile = $tempDir . '/test_' . uniqid() . '.txt';
if (@file_put_contents($testFile, 'test content')) {
    echo "  Successfully created test file: " . basename($testFile) . "\n";
    
    // Test file permissions
    chmod($testFile, 0666);
    echo "  File permissions: " . substr(sprintf('%o', fileperms($testFile)), -4) . "\n";
    
    // Try to remove the test file
    if (@unlink($testFile)) {
        echo "  Successfully removed test file.\n";
    } else {
        echo "  Warning: Could not remove test file.\n";
        echo "  Please run: sudo rm $testFile\n";
    }
} else {
    echo "  Failed to create test file! This is a critical error.\n";
    echo "  Please run this script with sudo permissions.\n";
}

// Add PHP configuration info
echo "\nPHP Configuration:\n";
echo "  upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "  post_max_size: " . ini_get('post_max_size') . "\n";
echo "  memory_limit: " . ini_get('memory_limit') . "\n";

// Generate a shell script that can be run with sudo
$shellScriptPath = $projectDir . '/bin/fix_temp_dir.sh';
$shellScript = <<<EOT
#!/bin/bash
# This script fixes permissions for upload directories
# Run with sudo: sudo bash {$shellScriptPath}

# Create directories
mkdir -p {$projectDir}/var/uploads/temp
mkdir -p {$projectDir}/public/uploads/images

# Set aggressive permissions
chmod -R 777 {$projectDir}/var/uploads
chmod -R 777 {$projectDir}/public/uploads

# Set ownership to web server user
chown -R {$webServerUser}:{$webServerUser} {$projectDir}/var/uploads
chown -R {$webServerUser}:{$webServerUser} {$projectDir}/public/uploads

# Clean up any existing temp files
rm -f {$projectDir}/var/uploads/temp/php*

echo "Permissions fixed successfully!"
EOT;

file_put_contents($shellScriptPath, $shellScript);
chmod($shellScriptPath, 0755);

echo "\nCreated shell script for sudo execution: $shellScriptPath\n";
echo "If this script didn't fix the issue, please run:\n";
echo "  sudo bash $shellScriptPath\n\n";

echo "PHP upload directory fix completed!\n";
echo "Restart your web server with:\n";
echo "  sudo systemctl restart apache2  # For Apache\n";
echo "  sudo systemctl restart nginx    # For Nginx\n";
