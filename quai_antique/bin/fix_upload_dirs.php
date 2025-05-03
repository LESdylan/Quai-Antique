<?php
/**
 * Fix Upload Directories Script
 * 
 * This script creates and sets proper permissions for image upload directories
 */

// Define paths relative to project root
$projectDir = dirname(__DIR__);
$varDir = $projectDir . '/var';
$uploadsDir = $projectDir . '/public/uploads';
$imagesDir = $uploadsDir . '/images';
$tempDir = $varDir . '/uploads/temp';

echo "Quai Antique - Fix Upload Directories\n";
echo "=====================================\n\n";

function createDirWithPermissions($dir) {
    echo "Checking directory: $dir\n";
    
    if (!file_exists($dir)) {
        echo "  - Creating directory...\n";
        if (!mkdir($dir, 0777, true)) {
            echo "ERROR: Failed to create directory: $dir\n";
            return false;
        }
    }
    
    echo "  - Setting permissions...\n";
    if (!chmod($dir, 0777)) {
        echo "ERROR: Failed to set permissions on directory: $dir\n";
        return false;
    }
    
    echo "  - Directory ready and writable ✓\n";
    return true;
}

// Create or fix permissions for all needed directories
createDirWithPermissions($varDir);
createDirWithPermissions($uploadsDir);
createDirWithPermissions($imagesDir);
createDirWithPermissions($tempDir);

// Test file creation in the temp directory
echo "\nTesting file creation in temp directory...\n";
$testFile = $tempDir . '/test_' . uniqid() . '.tmp';

try {
    if (file_put_contents($testFile, 'test')) {
        echo "Successfully created test file: $testFile ✓\n";
        unlink($testFile);
        echo "Successfully deleted test file ✓\n";
    } else {
        echo "ERROR: Could not create test file\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nCurrent directory permissions:\n";
system("ls -la $varDir/uploads");
system("ls -la $uploadsDir");

echo "\nDirectories setup complete! You should now be able to upload images.\n";
