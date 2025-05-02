#!/bin/bash

echo "=== Project Directory Permission Fix Script ==="
echo "This script will restore your ability to write to the project files"

PROJECT_DIR="/home/dlesieur/Documents/Studi/Quai-Antique/quai_antique"
USERNAME=$(whoami)

echo "Setting proper ownership for $PROJECT_DIR"
echo "Your username: $USERNAME"

# Change ownership to your user, but keep www-data as group for Apache
sudo chown -R $USERNAME:www-data $PROJECT_DIR
sudo chmod -R 775 $PROJECT_DIR

# Make sure you're in the www-data group
sudo usermod -a -G www-data $USERNAME

# Apply special permissions to var directory for Symfony
if [ -d "$PROJECT_DIR/var" ]; then
    echo "Setting special permissions for Symfony var directory..."
    sudo chmod -R g+w $PROJECT_DIR/var
    
    # If ACL is available, use it for more precise permissions
    if command -v setfacl &> /dev/null; then
        echo "Setting up ACLs for better permission management..."
        sudo apt-get install -y acl
        sudo setfacl -R -m u:$USERNAME:rwX -m u:www-data:rwX $PROJECT_DIR/var
        sudo setfacl -dR -m u:$USERNAME:rwX -m u:www-data:rwX $PROJECT_DIR/var
    fi
fi

echo "Fixing composer.json permissions if it exists..."
if [ -f "$PROJECT_DIR/composer.json" ]; then
    sudo chmod 664 $PROJECT_DIR/composer.json
fi

echo "=== Permission fix completed ==="
echo "You may need to log out and log back in for group changes to take effect."
echo "After logging back in, verify with: touch $PROJECT_DIR/test_file && rm $PROJECT_DIR/test_file"
