#!/bin/bash
# This script fixes permissions for upload directories
# Run with sudo: sudo bash /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/bin/fix_temp_dir.sh

# Create directories
mkdir -p /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/var/uploads/temp
mkdir -p /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public/uploads/images

# Set aggressive permissions
chmod -R 777 /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/var/uploads
chmod -R 777 /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public/uploads

# Set ownership to web server user
chown -R www-data:www-data /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/var/uploads
chown -R www-data:www-data /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/public/uploads

# Clean up any existing temp files
rm -f /home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/var/uploads/temp/php*

echo "Permissions fixed successfully!"