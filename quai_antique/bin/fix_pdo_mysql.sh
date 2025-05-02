#!/bin/bash

echo "=== Enhanced PDO MySQL Fix Script ==="
echo "This script will attempt to fix package and PDO MySQL issues"

# Step 1: Fix dpkg interrupted state first
echo "Fixing interrupted dpkg state..."
sudo dpkg --configure -a
if [ $? -eq 0 ]; then
    echo "✅ Successfully fixed dpkg state"
else
    echo "❌ Failed to fix dpkg state, this may cause further issues"
fi

# Step 2: Make sure all packages are up-to-date
echo "Updating system packages..."
sudo apt update && sudo apt dist-upgrade -y

# Step 3: Remove problematic PHP MySQL packages
echo "Removing any problematic PHP MySQL packages..."
sudo apt remove --purge -y php-mysql php8.3-mysql php-mysqlnd php8.3-mysqlnd
sudo apt autoremove -y

# Step 4: Clear PHP extension cache and configuration
echo "Clearing PHP configuration..."
sudo rm -f /etc/php/8.3/cli/conf.d/*mysql*
sudo rm -f /etc/php/8.3/apache2/conf.d/*mysql*
sudo rm -f /etc/php/8.3/fpm/conf.d/*mysql*

# Step 5: Reinstall PHP and MySQL extensions in the correct order
echo "Installing packages in the correct order..."
sudo apt install -y php8.3-cli php8.3-common
sudo apt install -y php8.3-mysqlnd
sudo apt install -y php8.3-mysql
sudo apt install -y php-mysql

# Step 6: Manual extension load order enforcement
echo "Creating custom configuration to enforce load order..."

# Create a custom PHP configuration to load mysqlnd before pdo_mysql
CUSTOM_CONFIG_DIR="/etc/php/8.3/mods-available"
sudo mkdir -p "$CUSTOM_CONFIG_DIR"

sudo tee "$CUSTOM_CONFIG_DIR/mysqlnd-first.ini" > /dev/null << EOF
; Custom configuration to load mysqlnd before pdo_mysql
extension=mysqlnd.so
EOF

sudo tee "$CUSTOM_CONFIG_DIR/pdo_mysql-after.ini" > /dev/null << EOF
; Custom configuration to load pdo_mysql after mysqlnd
extension=pdo_mysql.so
EOF

# Enable custom configurations with priority order
sudo ln -sf "$CUSTOM_CONFIG_DIR/mysqlnd-first.ini" "/etc/php/8.3/cli/conf.d/10-mysqlnd.ini"
sudo ln -sf "$CUSTOM_CONFIG_DIR/pdo_mysql-after.ini" "/etc/php/8.3/cli/conf.d/20-pdo_mysql.ini"

if [ -d "/etc/php/8.3/apache2" ]; then
    sudo ln -sf "$CUSTOM_CONFIG_DIR/mysqlnd-first.ini" "/etc/php/8.3/apache2/conf.d/10-mysqlnd.ini"
    sudo ln -sf "$CUSTOM_CONFIG_DIR/pdo_mysql-after.ini" "/etc/php/8.3/apache2/conf.d/20-pdo_mysql.ini"
fi

if [ -d "/etc/php/8.3/fpm" ]; then
    sudo ln -sf "$CUSTOM_CONFIG_DIR/mysqlnd-first.ini" "/etc/php/8.3/fpm/conf.d/10-mysqlnd.ini"
    sudo ln -sf "$CUSTOM_CONFIG_DIR/pdo_mysql-after.ini" "/etc/php/8.3/fpm/conf.d/20-pdo_mysql.ini"
fi

# Step 7: Try a nuclear option - download and compile PHP MySQL extension from source
echo "Would you like to attempt the nuclear option? This will download and compile PHP MySQL extension from source. (y/n)"
read -p "> " NUCLEAR_OPTION

if [[ "$NUCLEAR_OPTION" == "y" || "$NUCLEAR_OPTION" == "Y" ]]; then
    echo "Installing build dependencies..."
    sudo apt install -y php8.3-dev build-essential autoconf

    echo "Creating temporary directory..."
    TEMP_DIR=$(mktemp -d)
    cd "$TEMP_DIR"

    echo "Downloading PHP source code..."
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    PHP_MAJOR_VERSION=$(echo "$PHP_VERSION" | cut -d. -f1-2)
    wget "https://www.php.net/distributions/php-${PHP_MAJOR_VERSION}.tar.gz"
    tar xzf "php-${PHP_MAJOR_VERSION}.tar.gz"
    
    echo "Building mysqlnd extension..."
    cd "php-${PHP_MAJOR_VERSION}/ext/mysqlnd"
    phpize
    ./configure
    make
    sudo make install
    
    echo "Building pdo_mysql extension (depends on mysqlnd)..."
    cd "../pdo_mysql"
    phpize
    ./configure
    make
    sudo make install
    
    echo "Cleaning up..."
    cd "$HOME"
    rm -rf "$TEMP_DIR"
fi

# Step 8: Restart services
echo "Restarting services..."
sudo systemctl restart apache2 || true
sudo systemctl restart php8.3-fpm || true

# Step 9: Verify if the issue is fixed
echo "Verifying if PDO MySQL is now loaded..."

# Create a temporary PHP script to check PDO MySQL
cat > /tmp/check_pdo.php << 'EOF'
<?php
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded extensions:\n";
foreach (get_loaded_extensions() as $ext) {
    echo "- $ext\n";
}
echo "PDO drivers available:\n";
if (class_exists('PDO')) {
    foreach (PDO::getAvailableDrivers() as $driver) {
        echo "- $driver\n";
    }
} else {
    echo "PDO class not available.\n";
}
EOF

php /tmp/check_pdo.php

echo
echo "=== Fix attempt completed ==="
echo
echo "If PDO MySQL is still not loading, you have a few alternatives:"
echo "1. Use the built-in PHP server for development: php -S 127.0.0.1:8000 -t public/"
echo "2. Continue with Symfony using SQLite instead of MySQL"
echo "3. Consider using a Docker setup for development"
echo
echo "To modify your Symfony project to use SQLite temporarily, edit your .env.local file:"
echo "DATABASE_URL=\"sqlite:///%kernel.project_dir%/var/data.db\""
