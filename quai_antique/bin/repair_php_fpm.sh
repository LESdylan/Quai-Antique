#!/bin/bash

# Repair script for PHP 8.3 FPM and Apache2 integration issues
# Created for Quai-Antique project

echo "=== Apache2 + PHP-FPM Integration Repair Script ==="
echo "This script will fix the integration between Apache2 and PHP-FPM"
echo

# Function to check if command succeeded
check_status() {
    if [ $? -eq 0 ]; then
        echo "✅ Success: $1"
    else
        echo "❌ Failed: $1"
        echo "Please check error messages above"
        exit 1
    fi
}

# Stop PHP-FPM and Apache services
echo "Stopping services..."
sudo systemctl stop php8.3-fpm apache2 || true

# Kill any hanging processes
echo "Killing any hanging processes..."
sudo pkill -9 php-fpm || true

# Remove PHP socket file if it exists
echo "Cleaning up PHP socket..."
sudo rm -f /run/php/php-fpm.sock || true
sudo rm -f /run/php/php8.3-fpm.sock || true

# Fix package state
echo "Fixing package state..."
sudo dpkg --configure -a
check_status "Package state fix"

# Remove conflicting modules
echo "Removing potentially conflicting modules..."
sudo apt-get purge -y libapache2-mod-php8.3 || true
check_status "Removing Apache PHP module"

# Purge problematic packages
echo "Removing problematic PHP packages..."
sudo apt-get purge -y php8.3-fpm php-fpm php-mysql php8.3-mysql
check_status "Removing packages"

# Clean up
echo "Cleaning package cache..."
sudo apt-get autoremove -y
sudo apt-get autoclean
check_status "Cleaning package cache"

# Fix the integration between Apache and PHP-FPM
echo "Installing PHP-FPM integration for Apache..."
sudo apt-get install -y libapache2-mod-fcgid apache2-suexec-pristine
check_status "Installing Apache FastCGI modules"

echo "Enabling necessary Apache modules..."
sudo a2enmod actions fcgid alias proxy_fcgi setenvif
sudo a2enconf php8.3-fpm
check_status "Enabling Apache modules"

# Install PHP-FPM and MySQL packages fresh
echo "Installing PHP-FPM and extensions in the correct order..."
sudo apt-get install -y php8.3-fpm php8.3-common php8.3-mysql php8.3-mysqlnd php8.3-cli
check_status "Installing PHP packages"

# Enable the PDO MySQL extension explicitly
echo "Enabling PDO MySQL extension..."
sudo phpenmod -v 8.3 pdo_mysql
check_status "Enabling PDO MySQL"

# Fix PHP configuration if needed
echo "Checking for duplicate extension entries in PHP configuration..."
if grep -q "^extension=pdo_mysql" /etc/php/8.3/fpm/php.ini; then
    echo "Removing duplicate extension entry..."
    sudo sed -i '/^extension=pdo_mysql/d' /etc/php/8.3/fpm/php.ini
fi

# Configure Apache to use PHP-FPM
echo "Configuring Apache to use PHP-FPM..."
sudo tee /etc/apache2/conf-available/php8.3-fpm.conf > /dev/null << EOF
<FilesMatch "\.php$">
    SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
</FilesMatch>
EOF
sudo a2enconf php8.3-fpm
check_status "Configuring Apache PHP-FPM integration"

# Make sure PHP-FPM user has proper permissions
echo "Setting correct permissions..."
PHP_FPM_USER=$(grep -r "^user =" /etc/php/8.3/fpm/pool.d/ | cut -d= -f2 | tr -d ' ')
if [ -z "$PHP_FPM_USER" ]; then
    PHP_FPM_USER="www-data"
fi
echo "PHP-FPM is running as user: $PHP_FPM_USER"
sudo usermod -a -G $PHP_FPM_USER www-data
sudo usermod -a -G www-data $PHP_FPM_USER
check_status "Setting user permissions"

# Restart services in the correct order
echo "Starting services in the correct order..."
sudo systemctl restart php8.3-fpm
check_status "Starting PHP-FPM"
sleep 2
sudo systemctl restart apache2
check_status "Starting Apache"

# Test that both services are running correctly
echo "Verifying services status..."
sudo systemctl status php8.3-fpm --no-pager | grep "active (running)"
check_status "PHP-FPM service status"
sudo systemctl status apache2 --no-pager | grep "active (running)"
check_status "Apache service status"

# Create a test file to verify PHP-FPM works with Apache
echo "Creating test PHP file..."
TEST_PATH="/var/www/html/phpinfo-test.php"
echo "<?php phpinfo(); ?>" | sudo tee $TEST_PATH > /dev/null
sudo chown $PHP_FPM_USER:$PHP_FPM_USER $TEST_PATH
check_status "Creating test file"

echo
echo "=== Repair completed ==="
echo "If everything shows as successful, your Apache2 and PHP-FPM integration should now be working."
echo "To test, visit: http://localhost/phpinfo-test.php"
echo "You should see the PHP info page. If it works, delete the test file for security:"
echo "sudo rm $TEST_PATH"
