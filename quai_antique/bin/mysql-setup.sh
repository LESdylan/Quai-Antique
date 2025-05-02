#!/bin/bash

# MySQL Setup Helper for PHP 8.3
# This script focuses exclusively on MySQL configuration

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

echo -e "${BLUE}${BOLD}=========================================================${NC}"
echo -e "${BLUE}${BOLD}            MySQL Extension Setup for PHP                 ${NC}"
echo -e "${BLUE}${BOLD}=========================================================${NC}"

# Get PHP version information
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
PHP_EXTENSION_DIR=$(php -r 'echo ini_get("extension_dir");')
PHP_INI_PATH=$(php -r 'echo php_ini_loaded_file();')

echo -e "${BLUE}PHP Version:${NC} $PHP_VERSION"
echo -e "${BLUE}PHP Extension Directory:${NC} $PHP_EXTENSION_DIR"
echo -e "${BLUE}PHP Configuration File:${NC} $PHP_INI_PATH"

# Check if we're in a derivative environment (e.g., MAMP, Docker)
IS_DOCKER=0
if [ -f /.dockerenv ]; then
    IS_DOCKER=1
    echo -e "${YELLOW}Running in Docker environment${NC}"
fi

# Check if we're on a WSL instance
IS_WSL=0
if grep -q Microsoft /proc/version 2>/dev/null; then
    IS_WSL=1
    echo -e "${YELLOW}Running on Windows Subsystem for Linux${NC}"
fi

# Advanced diagnostics
echo -e "\n${BLUE}${BOLD}Advanced Extension Diagnostics:${NC}"

# Check if extension directory exists
if [ -d "$PHP_EXTENSION_DIR" ]; then
    echo -e "${GREEN}✓ Extension directory exists: $PHP_EXTENSION_DIR${NC}"
    
    # List all MySQL related files in extension directory
    echo -e "\n${BLUE}Looking for MySQL extensions in $PHP_EXTENSION_DIR:${NC}"
    ls -la $PHP_EXTENSION_DIR | grep -i mysql || echo "No MySQL files found"
    ls -la $PHP_EXTENSION_DIR | grep -i maria || echo "No MariaDB files found"
    ls -la $PHP_EXTENSION_DIR | grep -i pdo || echo "No PDO files found"
else
    echo -e "${RED}✗ Extension directory does not exist: $PHP_EXTENSION_DIR${NC}"
    
    # Try to create extension directory
    if [ "$EUID" -eq 0 ]; then
        echo -e "Creating extension directory..."
        mkdir -p "$PHP_EXTENSION_DIR"
        if [ -d "$PHP_EXTENSION_DIR" ]; then
            echo -e "${GREEN}✓ Extension directory created: $PHP_EXTENSION_DIR${NC}"
        else
            echo -e "${RED}✗ Failed to create extension directory${NC}"
        fi
    else
        echo -e "${YELLOW}Run with sudo to create extension directory${NC}"
    fi
fi

# Search for MySQL extensions in other locations
echo -e "\n${BLUE}${BOLD}Searching for MySQL extensions in other locations:${NC}"
echo -e "(This may take a few moments...)"

# Check common alternate PHP extension locations based on distribution patterns
PHP_ALT_DIRS=(
    "/usr/lib/php"
    "/usr/lib/php/modules"
    "/usr/lib64/php/modules"
    "/usr/local/lib/php/extensions"
    "/opt/homebrew/lib/php/pecl"
)

FOUND_MYSQLND=0
FOUND_PDO_MYSQL=0
MYSQLND_PATH=""
PDO_MYSQL_PATH=""

# More targeted search to be faster
for dir in "${PHP_ALT_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "Checking $dir..."
        # Look for mysqlnd.so
        FIND_MYSQLND=$(find "$dir" -name "mysqlnd.so" 2>/dev/null)
        if [ ! -z "$FIND_MYSQLND" ]; then
            MYSQLND_PATH=$FIND_MYSQLND
            FOUND_MYSQLND=1
            echo -e "${GREEN}✓ Found mysqlnd.so: $MYSQLND_PATH${NC}"
        fi
        
        # Look for pdo_mysql.so
        FIND_PDO_MYSQL=$(find "$dir" -name "pdo_mysql.so" 2>/dev/null)
        if [ ! -z "$FIND_PDO_MYSQL" ]; then
            PDO_MYSQL_PATH=$FIND_PDO_MYSQL
            FOUND_PDO_MYSQL=1
            echo -e "${GREEN}✓ Found pdo_mysql.so: $PDO_MYSQL_PATH${NC}"
        fi
    fi
done

if [ "$FOUND_MYSQLND" -eq 0 ]; then
    echo -e "${RED}✗ mysqlnd.so not found${NC}"
fi

if [ "$FOUND_PDO_MYSQL" -eq 0 ]; then
    echo -e "${RED}✗ pdo_mysql.so not found${NC}"
fi

# Detect package manager and PHP packaging pattern
PACKAGE_MANAGER=""
PHP_PACKAGE_FORMAT=""

if command -v apt-get > /dev/null; then
    PACKAGE_MANAGER="apt-get"
    PHP_PACKAGE_FORMAT="php%d.%d-%s"
elif command -v dnf > /dev/null; then
    PACKAGE_MANAGER="dnf"
    PHP_PACKAGE_FORMAT="php%d%d-%s"
elif command -v yum > /dev/null; then
    PACKAGE_MANAGER="yum"
    PHP_PACKAGE_FORMAT="php%d%d-%s"
elif command -v zypper > /dev/null; then
    PACKAGE_MANAGER="zypper"
    PHP_PACKAGE_FORMAT="php%d_%d-%s"
elif command -v pacman > /dev/null; then
    PACKAGE_MANAGER="pacman"
    PHP_PACKAGE_FORMAT="php%d%d-%s"
elif command -v brew > /dev/null; then
    PACKAGE_MANAGER="brew"
    PHP_PACKAGE_FORMAT="php@%d.%d"
fi

echo -e "\n${BLUE}${BOLD}System Information:${NC}"
echo -e "Package Manager: ${PACKAGE_MANAGER:-Unknown}"
if command -v lsb_release > /dev/null; then
    echo -e "Distribution: $(lsb_release -d | cut -f2)"
elif [ -f /etc/os-release ]; then
    echo -e "Distribution: $(grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d \")"
fi

# PHP 8.3 specific checks and solutions
if [ "$PHP_MAJOR_VERSION" = "8" ] && [ "$PHP_MINOR_VERSION" = "3" ]; then
    echo -e "\n${YELLOW}${BOLD}PHP 8.3 Detected - Special Handling Required${NC}"
    
    # Check for needed packages
    if [ "$PACKAGE_MANAGER" = "apt-get" ]; then
        # For Debian/Ubuntu
        echo -e "\n${BLUE}${BOLD}PHP 8.3 MySQL Extension Installation:${NC}"
        
        # Force-yes for demonstration purposes in this script
        INSTALL_CMD="sudo apt-get install -y php8.3-mysql"
        
        echo -e "Run the following commands to install MySQL extensions:"
        echo -e "${BOLD}sudo add-apt-repository ppa:ondrej/php${NC} (if PHP 8.3 is from this PPA)"
        echo -e "${BOLD}sudo apt-get update${NC}"
        echo -e "${BOLD}$INSTALL_CMD${NC}"
        
        # Check if we can install automatically
        if [ "$EUID" -eq 0 ]; then
            echo -e "\nWould you like to run these commands now? (y/n)"
            read -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                # Only add PPA if not already present
                if ! grep -q "ondrej/php" /etc/apt/sources.list.d/* 2>/dev/null; then
                    echo "Adding ondrej/php PPA..."
                    apt-get update
                    apt-get install -y software-properties-common
                    add-apt-repository ppa:ondrej/php -y
                fi
                
                apt-get update
                apt-get install -y php8.3-mysql
                
                # Verify installation
                if [ -f "/usr/lib/php/20230831/mysqlnd.so" ]; then
                    echo -e "${GREEN}✓ mysqlnd.so installed successfully${NC}"
                else
                    echo -e "${RED}✗ mysqlnd.so not installed or not found${NC}"
                fi
                
                if [ -f "/usr/lib/php/20230831/pdo_mysql.so" ]; then
                    echo -e "${GREEN}✓ pdo_mysql.so installed successfully${NC}"
                else
                    echo -e "${RED}✗ pdo_mysql.so not installed or not found${NC}"
                fi
                
                # Enable extensions
                if command -v phpenmod > /dev/null; then
                    phpenmod -v 8.3 mysqlnd pdo_mysql mysqli
                fi
                
                # Restart services
                if command -v systemctl > /dev/null; then
                    if systemctl is-active --quiet apache2; then
                        systemctl restart apache2
                    fi
                    if systemctl is-active --quiet php8.3-fpm; then
                        systemctl restart php8.3-fpm
                    fi
                elif command -v service > /dev/null; then
                    service apache2 restart 2>/dev/null || true
                    service php8.3-fpm restart 2>/dev/null || true
                fi
            fi
        fi
    fi
    
    # Manual solution section
    echo -e "\n${BLUE}${BOLD}Manual Solutions for PHP 8.3:${NC}"
    echo -e "If automatic installation failed, try these steps:"
    echo -e "\n1. ${BOLD}Use PECL to compile and install the extensions:${NC}"
    echo -e "   sudo apt-get install php8.3-dev"
    echo -e "   sudo pecl install mysqlnd pdo_mysql"
    echo -e "\n2. ${BOLD}Create symbolic links to extensions from other PHP versions:${NC}"
    echo -e "   sudo ln -s /path/to/found/mysqlnd.so $PHP_EXTENSION_DIR/mysqlnd.so"
    echo -e "   sudo ln -s /path/to/found/pdo_mysql.so $PHP_EXTENSION_DIR/pdo_mysql.so"
    
    # PHP Version compatibility
    echo -e "\n${BLUE}${BOLD}Alternative PHP Version:${NC}"
    echo -e "If you're having trouble with PHP 8.3, consider using PHP 8.2 which has better package support:"
    echo -e "   sudo apt-get install php8.2 php8.2-mysql php8.2-cli"
    echo -e "   sudo update-alternatives --set php /usr/bin/php8.2"
fi

# Check for SQLite as an alternative
SQLITE_AVAILABLE=$(php -r 'echo extension_loaded("pdo_sqlite") ? "yes" : "no";')
if [ "$SQLITE_AVAILABLE" = "yes" ]; then
    echo -e "\n${BLUE}${BOLD}SQLite Alternative:${NC}"
    echo -e "${GREEN}✓ SQLite is available as an alternative database${NC}"
    echo -e "To use SQLite instead of MySQL, update your .env file with:"
    echo -e "${BOLD}DATABASE_URL=\"sqlite:///%kernel.project_dir%/var/data.db\"${NC}"
else
    echo -e "\n${RED}✗ SQLite is not available either${NC}"
    if [ "$PACKAGE_MANAGER" = "apt-get" ]; then
        echo -e "Install SQLite with: ${BOLD}sudo apt-get install php8.3-sqlite3${NC}"
    fi
fi

echo -e "\n${BLUE}${BOLD}=========================================================${NC}"
echo -e "${BLUE}${BOLD}          MySQL Setup Helper Complete                    ${NC}"
echo -e "${BLUE}${BOLD}=========================================================${NC}"
