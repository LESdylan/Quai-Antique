<?php
/**
 * Fix Upload Temporary Directory Issues
 * 
 * This script focuses specifically on the temporary upload directory problem
 * that causes the error "file does not exist or is not readable"
 */

echo "PHP Upload Temporary Directory Fixer\n";
echo "===================================\n\n";

// Define the project directory and uploads temp directory
$projectDir = dirname(__DIR__);
$tempDir = $projectDir . '/var/uploads/temp';

// Step 1: Check if the directory exists
echo "Checking if directory exists: $tempDir\n";
if (!is_dir($tempDir)) {
    echo "Creating directory structure...\n";
    
    // Create the directory structure recursively
    if (!mkdir($tempDir, 0777, true)) {
        echo "ERROR: Failed to create directory: $tempDir\n";
        echo "Try running this script with sudo\n";
        exit(1);
    }
    echo "Directory created successfully\n";
} else {
    echo "Directory already exists\n";
}

// Step 2: Set permissions
echo "Setting full permissions (777) on directory...\n";
if (!chmod($tempDir, 0777)) {
    echo "ERROR: Failed to set permissions on $tempDir\n";
    echo "Try running this script with sudo\n";
    exit(1);
}
echo "Permissions set successfully\n";

// Step 3: PHP runtime configuration
echo "Configuring PHP to use this directory...\n";

// Get the current user
$currentUser = get_current_user();
$webServerUser = 'www-data'; // Typical for Apache on Ubuntu/Debian

// Create an .htaccess file
$htaccessPath = $projectDir . '/public/.htaccess';
$htaccessContent = "# Set upload tmp directory\n";
$htaccessContent .= "php_value upload_tmp_dir " . $tempDir . "\n";
$htaccessContent .= "php_value upload_max_filesize 10M\n";
$htaccessContent .= "php_value post_max_size 12M\n";

// Only add this if the file doesn't already have these settings
if (!file_exists($htaccessPath) || strpos(file_get_contents($htaccessPath), 'upload_tmp_dir') === false) {
    file_put_contents($htaccessPath, $htaccessContent, FILE_APPEND);
    echo "Added PHP settings to $htaccessPath\n";
} else {
    echo ".htaccess already contains upload settings\n";
}

// Create a phpinfo file to verify settings
$phpinfoPath = $tempDir . '/phpinfo.php';
file_put_contents($phpinfoPath, '<?php phpinfo(); ?>');
chmod($phpinfoPath, 0777);

// Try creating a test file in the temp directory
$testFilePath = $tempDir . '/test_' . uniqid() . '.txt';
if (file_put_contents($testFilePath, 'Test file content')) {
    echo "✓ Successfully created test file: $testFilePath\n";
    unlink($testFilePath);
    echo "✓ Successfully deleted test file\n";
} else {
    echo "ERROR: Could not create test file in $tempDir\n";
    echo "This indicates a permission issue that needs to be fixed before uploads will work\n";
    exit(1);
}

// PHP-specific information
echo "\nPHP Environment Information:\n";
echo "* sys_get_temp_dir(): " . sys_get_temp_dir() . "\n";
echo "* upload_tmp_dir setting: " . ini_get('upload_tmp_dir') . "\n";
echo "* upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "* post_max_size: " . ini_get('post_max_size') . "\n";
echo "* Current user: $currentUser\n";
echo "* Web server user: $webServerUser (typically)\n";

echo "\nFix completed successfully!\n";
echo "The upload directory is now properly set up and has the correct permissions.\n";
echo "If you still encounter issues, restart your web server with:\n";
echo "sudo service apache2 restart # for Apache\n";
echo "OR\n";
echo "sudo service nginx restart # for Nginx\n";
