#!/bin/bash

echo "=== Enhanced Symfony Server Fix Script ==="

# Fix permissions more aggressively
echo "Fixing permissions for all Symfony config directories..."
sudo chown -R $USER:$USER ~/.symfony5/
chmod -R 777 ~/.symfony5/

# Reset the Symfony binary cache
echo "Resetting Symfony binary cache..."
rm -rf ~/.symfony/cache/*
symfony self:gc --all

# Advanced PHP MySQL installation
echo "Installing PHP MySQL with all dependencies..."
sudo apt update
sudo apt install -y php-common php-mysql php-mysqlnd
sudo apt install -y php8.3-common php8.3-mysql php8.3-mysqlnd
sudo apt install -y php8.2-common php8.2-mysql php8.2-mysqlnd
sudo phpenmod -v ALL pdo_mysql mysqli mysqlnd

# More aggressive solution for PDO MySQL extension
echo "Creating PDO MySQL extension for Symfony PHP..."
SYMFONY_PHP_DIR=$(find ~/.symfony5/php -name "ext" -type d 2>/dev/null)
SYMFONY_PHP_INI_DIR=$(find ~/.symfony5/php -name "*.ini" -type f 2>/dev/null | head -n 1 | xargs dirname 2>/dev/null)

if [ -n "$SYMFONY_PHP_DIR" ]; then
    echo "Found Symfony PHP extensions directory: $SYMFONY_PHP_DIR"
    
    # Find the system's PDO MySQL extension
    PDO_MYSQL_PATH=$(find /usr/lib/php -name "pdo_mysql.so" 2>/dev/null | head -n 1)
    
    if [ -n "$PDO_MYSQL_PATH" ]; then
        echo "Found system PDO MySQL at: $PDO_MYSQL_PATH"
        # Force create symbolic link
        ln -sf "$PDO_MYSQL_PATH" "$SYMFONY_PHP_DIR/pdo_mysql.so"
        echo "Created symbolic link for PDO MySQL"
    else
        echo "Could not find system pdo_mysql.so, trying alternative approach..."
        # Alternative approach - copy from php modules directory
        PHP_MODULES_DIR=$(php -i | grep "extension_dir" | head -n 1 | awk '{print $3}')
        if [ -f "$PHP_MODULES_DIR/pdo_mysql.so" ]; then
            cp "$PHP_MODULES_DIR/pdo_mysql.so" "$SYMFONY_PHP_DIR/"
            echo "Copied PDO MySQL from PHP modules directory"
        else
            echo "WARNING: Could not find pdo_mysql.so in the system"
        fi
    fi
    
    # Make sure extensions are enabled in PHP config
    if [ -n "$SYMFONY_PHP_INI_DIR" ]; then
        echo "Adding extensions to PHP config..."
        EXTENSION_FILE="$SYMFONY_PHP_INI_DIR/extensions.ini"
        echo "extension=pdo_mysql.so" > "$EXTENSION_FILE"
        echo "extension=mysqli.so" >> "$EXTENSION_FILE"
        echo "extension=mysqlnd.so" >> "$EXTENSION_FILE"
        chmod 777 "$EXTENSION_FILE"
    fi
else
    echo "Could not find Symfony PHP extensions directory."
fi

echo "=== Trying alternate approach ==="
echo "Reinstalling Symfony CLI..."

# Backup current directory
CURRENT_DIR=$(pwd)

# Reinstall Symfony CLI
curl -sS https://get.symfony.com/cli/installer | bash
# Move binary to make it accessible globally
if [ -f ~/.symfony5/bin/symfony ]; then
    sudo mv ~/.symfony5/bin/symfony /usr/local/bin/symfony
fi

# Return to the project directory
cd "$CURRENT_DIR" || exit

echo "=== Fix completed ==="
echo "Now try one of these approaches:"
echo
echo "1. Use Symfony server with the --no-tls option:"
echo "   symfony server:start --no-tls"
echo
echo "2. Or try PHP's built-in server instead:"
echo "   php -S 127.0.0.1:8000 -t public/"
echo
echo "3. If the issues persist, consider using Apache directly:"
echo "   symfony var:export-apache > /etc/apache2/sites-available/symfony.conf"
echo "   sudo a2ensite symfony && sudo systemctl reload apache2"
