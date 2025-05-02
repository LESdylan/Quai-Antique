#!/bin/bash

echo "=== Symfony Server NUCLEAR Fix Script ==="
echo "This script will aggressively fix the Symfony server issues"
echo

# First, ensure we're running as the correct user
if [ "$EUID" -eq 0 ]; then
  echo "This script should NOT be run as root or with sudo."
  echo "Please run it as your regular user."
  exit 1
fi

USERNAME=$(whoami)
SYMFONY_DIR="$HOME/.symfony5"
SYMFONY_PHP_DIR="$HOME/.symfony5/php"

# 1. Clean up the Symfony directories completely
echo "🧹 Cleaning up Symfony directories..."
rm -rf "$HOME/.symfony5/log"
rm -rf "$HOME/.symfony5/cache"
rm -rf "$HOME/.symfony/cache"

# 2. Make sure all your Symfony directories have the right permissions
echo "🔒 Setting correct permissions for Symfony directories..."
mkdir -p "$HOME/.symfony5/log"
mkdir -p "$HOME/.symfony5/cache"
mkdir -p "$HOME/.symfony/cache"
chmod -R 755 "$HOME/.symfony"
chmod -R 755 "$HOME/.symfony5"

# 3. Create a helper script to fix PDO MySQL for Symfony
echo "📜 Creating helper script for PDO MySQL..."

cat > "$HOME/fix_symfony_pdo.php" << 'EOF'
<?php
// Helper script to check PHP extension directory and available extensions
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP Extension Directory: " . ini_get('extension_dir') . "\n";
echo "\nLoaded Extensions:\n";
$loaded = get_loaded_extensions();
foreach ($loaded as $ext) {
    echo "- $ext\n";
}
echo "\nPDO MySQL Status: " . (in_array('pdo_mysql', $loaded) ? "LOADED ✅" : "NOT LOADED ❌") . "\n";
echo "\nSearching for PDO MySQL extension files...\n";
$pdo_files = glob("/usr/lib/php/**/*pdo_mysql*.so", GLOB_BRACE);
foreach ($pdo_files as $file) {
    echo "Found: $file\n";
}
EOF

echo "🔍 Checking current PHP configuration..."
php "$HOME/fix_symfony_pdo.php"

# 4. Check for pdo_mysql.so and create links for Symfony PHP
echo "🔗 Linking PDO MySQL extension for Symfony PHP..."

# Find all instances of pdo_mysql.so
PDO_SOURCES=$(find /usr/lib/php -name "pdo_mysql.so" 2>/dev/null)

# Find all Symfony PHP ext directories
find "$SYMFONY_PHP_DIR" -name "ext" -type d 2>/dev/null | while read -r ext_dir; do
    echo "Found Symfony PHP ext directory: $ext_dir"
    
    # Try each found pdo_mysql.so file
    for pdo_source in $PDO_SOURCES; do
        echo "Trying to link $pdo_source to $ext_dir/pdo_mysql.so"
        cp -f "$pdo_source" "$ext_dir/pdo_mysql.so" 2>/dev/null
        if [ $? -eq 0 ]; then
            chmod 755 "$ext_dir/pdo_mysql.so"
            echo "✅ Successfully copied PDO MySQL extension"
            
            # Also try to link the mysqlnd.so file which pdo_mysql depends on
            MYSQLND_SOURCE=$(find /usr/lib/php -name "mysqlnd.so" 2>/dev/null | head -n 1)
            if [ -n "$MYSQLND_SOURCE" ]; then
                cp -f "$MYSQLND_SOURCE" "$ext_dir/mysqlnd.so" 2>/dev/null
                chmod 755 "$ext_dir/mysqlnd.so" 2>/dev/null
                echo "✅ Copied mysqlnd dependency"
            fi
            
            break
        fi
    done
done

# 5. Create proper PHP configuration for Symfony
echo "📝 Creating PHP configuration for Symfony PHP-FPM..."
find "$SYMFONY_PHP_DIR" -name "*.ini" -not -path "*ext*" 2>/dev/null | while read -r ini_file; do
    dir=$(dirname "$ini_file")
    echo "Checking PHP config file: $ini_file"
    
    # Ensure the directory has correct permissions
    chmod 755 "$dir"
    
    # Check if we need to add the extension
    if ! grep -q "extension=pdo_mysql" "$ini_file"; then
        echo "extension=mysqlnd.so" >> "$ini_file"
        echo "extension=pdo_mysql.so" >> "$ini_file"
        echo "✅ Added PDO MySQL to config file"
    fi
    
    # Make sure the ini file has correct permissions
    chmod 644 "$ini_file"
done

# 6. Try a different approach - use built-in PHP server instead
echo "🚀 Setting up alternative PHP server solution..."

cat > "$HOME/Documents/Studi/Quai-Antique/quai_antique/server.php" << 'EOF'
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
EOF

# 7. Create convenient scripts to start web server
echo "📃 Creating convenient server start scripts..."

cat > "$HOME/Documents/Studi/Quai-Antique/quai_antique/start_php_server.sh" << 'EOF'
#!/bin/bash
# Start PHP's built-in server as an alternative to symfony server
echo "Starting PHP built-in web server on http://localhost:8000"
echo "Press Ctrl+C to stop the server"
cd "$(dirname "$0")"
php -S 0.0.0.0:8000 -t public server.php
EOF
chmod +x "$HOME/Documents/Studi/Quai-Antique/quai_antique/start_php_server.sh"

echo
echo "=== Nuclear fix completed ==="
echo
echo "Try running the Symfony server again:"
echo "symfony server:start --no-tls"
echo
echo "If it still doesn't work, use our fallback PHP server instead:"
echo "./start_php_server.sh"
echo
echo "This will start PHP's built-in web server which doesn't rely on PHP-FPM"
echo "Your site will be available at: http://localhost:8000"

# Clean up the helper script
rm -f "$HOME/fix_symfony_pdo.php"
