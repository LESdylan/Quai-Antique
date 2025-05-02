#!/bin/bash

echo "=== Apache mod_php Setup Script ==="
echo "This script will set up Apache with mod_php instead of PHP-FPM"
echo

# Step 1: Fix any interrupted dpkg state
echo "Fixing interrupted dpkg state... (this might take a while)"
sudo DEBIAN_FRONTEND=noninteractive dpkg --configure -a
echo "✅ Fixed package state"

# Step 2: Remove problematic PHP-FPM packages
echo "Removing PHP-FPM packages..."
sudo apt-get remove --purge -y php8.3-fpm php-fpm 
echo "✅ Removed PHP-FPM"

# Step 3: Install mod_php for Apache
echo "Installing Apache mod_php..."
sudo apt-get update
sudo apt-get install -y libapache2-mod-php8.3
echo "✅ Installed mod_php"

# Step 4: Enable mod_php in Apache
echo "Enabling mod_php in Apache..."
sudo a2enmod php8.3
echo "✅ Enabled mod_php"

# Step 5: Install PHP MySQL support for mod_php
echo "Installing PHP MySQL support..."
sudo apt-get install -y php8.3-mysql php8.3-common php8.3-xml php8.3-mbstring
echo "✅ Installed PHP MySQL extensions"

# Step 6: Update Apache config for improved PHP performance
echo "Updating Apache configuration for PHP..."

cat > /tmp/php.conf << EOF
<IfModule mod_php8.c>
    # Set recommended PHP settings
    php_flag display_errors Off
    php_value max_execution_time 30
    php_value max_input_time 60
    php_value memory_limit 128M
    php_value post_max_size 8M
    php_value upload_max_filesize 2M
    php_flag magic_quotes_gpc Off
    php_flag register_globals Off
    php_flag short_open_tag On
    php_flag safe_mode Off
</IfModule>
EOF

sudo mv /tmp/php.conf /etc/apache2/conf-available/php.conf
sudo a2enconf php
echo "✅ Updated Apache PHP configuration"

# Step 7: Configure Apache for Symfony
echo "Configuring Apache for Symfony..."

# Create Apache virtual host for Symfony
cat > /tmp/symfony.conf << EOF
<VirtualHost *:80>
    ServerName quai-antique.local
    ServerAlias localhost 127.0.0.1
    DocumentRoot /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public
    
    <Directory /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/quai_antique_error.log
    CustomLog \${APACHE_LOG_DIR}/quai_antique_access.log combined
</VirtualHost>
EOF

sudo mv /tmp/symfony.conf /etc/apache2/sites-available/symfony.conf
sudo a2ensite symfony
sudo a2dissite 000-default
echo "✅ Configured Apache for Symfony"

# Step 8: Set correct permissions
echo "Setting correct permissions..."
sudo chown -R $USER:www-data /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique
sudo chmod -R 775 /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/var
echo "✅ Set permissions"

# Step 9: Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
echo "✅ Restarted Apache"

echo
echo "=== Setup completed ==="
echo
echo "Your Symfony application should now be available at:"
echo "http://localhost or http://quai-antique.local"
echo
echo "If you still have issues, you can always use PHP's built-in server with:"
echo "./start_server.sh"
echo
echo "To verify this setup works:"
echo "php -i | grep mysql"
