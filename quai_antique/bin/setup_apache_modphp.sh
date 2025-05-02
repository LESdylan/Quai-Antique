#!/bin/bash

echo "=== Apache mod_php Setup Script ==="
echo "This script will remove PHP-FPM and set up Apache with mod_php"
echo

# Step 1: Remove problematic PHP-FPM packages
echo "Removing PHP-FPM packages to avoid conflicts..."
sudo apt-get purge -y php*-fpm php-fpm
sudo apt autoremove -y
echo "✅ Removed PHP-FPM"

# Step 2: Fix any package configuration issues
echo "Fixing package configuration issues..."
sudo dpkg --configure -a
echo "✅ Fixed package configuration"

# Step 3: Install Apache mod_php
echo "Installing Apache with mod_php..."
sudo apt-get update
sudo apt-get install -y apache2 libapache2-mod-php8.3
echo "✅ Installed mod_php"

# Step 4: Install required PHP modules for Symfony
echo "Installing required PHP modules for Symfony..."
sudo apt-get install -y php8.3-common php8.3-cli php8.3-xml php8.3-mbstring php8.3-intl php8.3-zip
echo "✅ Installed PHP modules"

# Step 5: Install PHP MySQL modules
echo "Installing PHP MySQL modules..."
sudo apt-get install -y php8.3-mysql
echo "✅ Installed PHP MySQL"

# Step 6: Enable required Apache modules
echo "Enabling Apache modules..."
sudo a2enmod php8.3
sudo a2enmod rewrite
echo "✅ Enabled Apache modules"

# Step 7: Set up Apache virtual host for Symfony
echo "Setting up Apache virtual host for your Symfony project..."
cat > /tmp/symfony.conf << EOF
<VirtualHost *:80>
    ServerName quai-antique.local
    ServerAlias localhost 127.0.0.1
    DocumentRoot /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public
    
    <Directory /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public>
        AllowOverride All
        Require all granted
        
        # For Symfony routing
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/quai_antique_error.log
    CustomLog \${APACHE_LOG_DIR}/quai_antique_access.log combined
</VirtualHost>
EOF

sudo mv /tmp/symfony.conf /etc/apache2/sites-available/quai-antique.conf
sudo a2ensite quai-antique.conf
sudo a2dissite 000-default.conf
echo "✅ Configured Apache virtual host"

# Step 8: Update hosts file
if ! grep -q "quai-antique.local" /etc/hosts; then
    echo "Updating hosts file..."
    echo "127.0.0.1 quai-antique.local" | sudo tee -a /etc/hosts
    echo "✅ Updated hosts file"
fi

# Step 9: Set proper permissions
echo "Setting proper permissions for your project..."
sudo chown -R $USER:www-data /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique
sudo chmod -R 775 /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/var
echo "✅ Set permissions"

# Step 10: Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
echo "✅ Restarted Apache"

# Step 11: Verify the PHP configuration
echo "Verifying the PHP configuration..."
php -m | grep -E 'mysql|pdo'
echo "✅ PHP configuration verified"

echo
echo "=== Setup completed ==="
echo
echo "Your Symfony application should now be accessible at:"
echo "http://localhost or http://quai-antique.local"
echo
echo "If you encounter any issues, check the Apache error logs:"
echo "sudo tail -f /var/log/apache2/quai_antique_error.log"
echo
echo "To test if the database connection works, try:"
echo "cd /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique"
echo "php bin/console doctrine:database:create"
