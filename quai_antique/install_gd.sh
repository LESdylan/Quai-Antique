#!/bin/bash

# Colors for better readability
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}==== PHP GD Extension Installation Helper ====${NC}"

# Get PHP version
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
echo -e "Detected PHP version: ${GREEN}$PHP_VERSION${NC}"

# Try to install the GD extension
echo -e "\n${YELLOW}Installing PHP GD Extension...${NC}"

if command -v apt-get &> /dev/null; then
    # For Debian/Ubuntu systems
    echo "Using apt package manager"
    sudo apt-get update
    sudo apt-get install -y php$PHP_VERSION-gd
elif command -v dnf &> /dev/null; then
    # For Fedora/RHEL systems
    echo "Using dnf package manager"
    sudo dnf install -y php-gd
elif command -v brew &> /dev/null; then
    # For macOS with Homebrew
    echo "Using Homebrew package manager"
    brew install php@$PHP_VERSION
    brew link --force --overwrite php@$PHP_VERSION
else
    echo -e "${RED}Could not determine package manager. Please install PHP GD extension manually.${NC}"
    exit 1
fi

# Check if GD was installed successfully
echo -e "\n${YELLOW}Verifying installation...${NC}"
if php -r 'exit(extension_loaded("gd") ? 0 : 1);'; then
    echo -e "${GREEN}✓ PHP GD extension is now installed!${NC}"
    
    # For Apache users
    echo -e "\n${YELLOW}If you're using Apache, you might need to restart it:${NC}"
    echo -e "sudo systemctl restart apache2"
    
    # For PHP-FPM users
    echo -e "\n${YELLOW}If you're using PHP-FPM, you might need to restart it:${NC}"
    echo -e "sudo systemctl restart php$PHP_VERSION-fpm"
    
    echo -e "\n${GREEN}You can now try running your migrations again:${NC}"
    echo -e "php bin/console make:migration"
else
    echo -e "${RED}✗ PHP GD extension installation failed.${NC}"
    echo -e "Please try to install it manually for PHP $PHP_VERSION"
fi
