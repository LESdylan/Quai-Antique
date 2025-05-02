#!/bin/bash

# Database setup helper script for Quai Antique project

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================================${NC}"
echo -e "${BLUE}       Quai Antique Database Setup Helper                ${NC}"
echo -e "${BLUE}=========================================================${NC}"

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo -e "${BLUE}PHP Version:${NC} $PHP_VERSION"

# Check for required extensions
echo -e "\n${BLUE}Checking required PHP extensions:${NC}"
if php -r 'echo extension_loaded("mysqlnd") ? "yes" : "no";' | grep -q "yes"; then
    echo -e "${GREEN}✓ mysqlnd extension is loaded${NC}"
else
    echo -e "${RED}✗ mysqlnd extension is not loaded${NC}"
    MISSING_EXT=1
fi

if php -r 'echo extension_loaded("pdo_mysql") ? "yes" : "no";' | grep -q "yes"; then
    echo -e "${GREEN}✓ pdo_mysql extension is loaded${NC}"
else
    echo -e "${RED}✗ pdo_mysql extension is not loaded${NC}"
    MISSING_EXT=1
fi

# If extensions are missing, provide installation instructions
if [ "$MISSING_EXT" == "1" ]; then
    echo -e "\n${YELLOW}Required MySQL extensions are missing. Here's how to install them:${NC}"
    
    # Detect the OS
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
    elif [ -f /usr/bin/sw_vers ]; then
        OS="macOS"
    else
        OS="Unknown"
    fi
    
    # For PHP version 8.x, extract the minor version
    PHP_MINOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
    
    echo -e "${BLUE}Detected OS:${NC} $OS"
    echo -e "${BLUE}PHP Version for packages:${NC} $PHP_MINOR_VERSION\n"
    
    # Ubuntu/Debian instructions
    if [[ "$OS" == *"Ubuntu"* || "$OS" == *"Debian"* ]]; then
        echo -e "${YELLOW}For Ubuntu/Debian:${NC}"
        echo -e "sudo apt-get update"
        echo -e "sudo apt-get install php$PHP_MINOR_VERSION-mysql"
        echo -e "sudo phpenmod -v $PHP_MINOR_VERSION mysqlnd pdo_mysql"
        echo -e "sudo service apache2 restart # Or php-fpm if you're using it\n"
    
    # macOS instructions
    elif [[ "$OS" == *"macOS"* ]]; then
        echo -e "${YELLOW}For macOS with Homebrew:${NC}"
        echo -e "brew install php"
        echo -e "# or if using a specific version:"
        echo -e "brew install php@$PHP_MINOR_VERSION"
        echo -e "brew services restart php\n"
    
    # Generic Linux instructions
    else
        echo -e "${YELLOW}For your system:${NC}"
        echo -e "1. Install PHP MySQL extensions (package name depends on your system)"
        echo -e "2. Make sure extensions are enabled in your php.ini"
        echo -e "3. Restart your web server or PHP-FPM service\n"
    fi
    
    # Find the PHP configuration file
    PHP_INI_PATH=$(php -r 'echo php_ini_loaded_file();')
    echo -e "${BLUE}Your PHP configuration file:${NC} $PHP_INI_PATH"
    echo -e "You may need to edit this file to enable the extensions if installation alone doesn't work.\n"
    
    # Fallback option
    echo -e "${YELLOW}Temporary fallback to SQLite:${NC}"
    echo -e "Until you get MySQL working, you can use SQLite by running:"
    echo -e "php bin/console doctrine:database:create --env=sqlite\n"
fi

# Check if MySQL is running
echo -e "\n${BLUE}Checking if MySQL server is running:${NC}"
if command -v mysqladmin &> /dev/null; then
    if mysqladmin ping -h localhost --silent; then
        echo -e "${GREEN}✓ MySQL server is running${NC}"
    else
        echo -e "${RED}✗ MySQL server is not running${NC}"
        echo -e "${YELLOW}Start your MySQL server:${NC}"
        echo -e "sudo service mysql start # For Ubuntu/Debian"
        echo -e "mysql.server start # For macOS"
    fi
else
    echo -e "${YELLOW}mysqladmin not found, can't check if MySQL is running${NC}"
fi

echo -e "\n${BLUE}=========================================================${NC}"
echo -e "${BLUE}             Thank you for using this script             ${NC}"
echo -e "${BLUE}=========================================================${NC}"
