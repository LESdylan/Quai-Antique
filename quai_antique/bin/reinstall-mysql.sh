#!/bin/bash

# Complete PHP MySQL Extension Reinstallation Script
# This script completely reinstalls PHP and MySQL extensions

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Function to print section headers
print_header() {
    echo -e "\n${BLUE}${BOLD}==================================================================${NC}"
    echo -e "${BLUE}${BOLD} $1 ${NC}"
    echo -e "${BLUE}${BOLD}==================================================================${NC}"
}

# Function to check if command succeeded
check_success() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Success: $1${NC}"
    else
        echo -e "${RED}✗ Failed: $1${NC}"
        if [ "$2" == "exit" ]; then
            echo -e "${RED}Exiting due to critical failure${NC}"
            exit 1
        fi
    fi
}

print_header "PHP MySQL Extension Complete Reinstallation"

# Check for root permissions
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}This script requires root permissions.${NC}"
    echo -e "Please run with: ${BOLD}sudo bash bin/reinstall-mysql.sh${NC}"
    exit 1
fi

# Get PHP version information
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
PHP_EXTENSION_DIR=$(php -r 'echo ini_get("extension_dir");')
PHP_INI_PATH=$(php -r 'echo php_ini_loaded_file();')

echo -e "${BLUE}PHP Version:${NC} $PHP_VERSION"
echo -e "${BLUE}PHP Extension Directory:${NC} $PHP_EXTENSION_DIR"
echo -e "${BLUE}PHP Configuration File:${NC} $PHP_INI_PATH"

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_NAME=$NAME
    OS_VERSION=$VERSION_ID
    echo -e "${BLUE}Detected OS:${NC} $OS_NAME $OS_VERSION"
else
    echo -e "${RED}Cannot detect operating system${NC}"
    exit 1
fi

# Backup current PHP configuration
print_header "Backing Up Current Configuration"

BACKUP_DIR="/tmp/php-mysql-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p $BACKUP_DIR
check_success "Created backup directory at $BACKUP_DIR"

# Backup PHP configuration files
if [ -d /etc/php/$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION/ ]; then
    cp -r /etc/php/$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION/ $BACKUP_DIR/
    check_success "Backed up PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION configuration files"
fi

# Check if ondrej PPA is installed (for Ubuntu/Debian)
if [[ "$OS_NAME" == *"Ubuntu"* || "$OS_NAME" == *"Debian"* ]]; then
    print_header "Setting Up PHP Repository"
    
    # Install prerequisites
    apt-get update
    apt-get install -y software-properties-common apt-transport-https ca-certificates
    check_success "Installed prerequisites"
    
    # Add the PPA if not already present
    if ! grep -q "ondrej/php" /etc/apt/sources.list.d/* 2>/dev/null; then
        echo -e "${YELLOW}Adding ondrej/php PPA for better PHP support...${NC}"
        add-apt-repository ppa:ondrej/php -y
        check_success "Added ondrej/php PPA" "exit"
    else
        echo -e "${GREEN}✓ ondrej/php PPA already installed${NC}"
    fi
    
    apt-get update
    check_success "Updated package lists"
fi

# Uninstall existing MySQL packages for PHP 8.3
print_header "Removing Existing PHP MySQL Packages"
apt-get remove --purge -y php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-mysql php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-mysqli php-mysql php-mysqli
check_success "Removed existing PHP MySQL packages"

# Clean up any residual configs
apt-get autoremove -y
apt-get autoclean
check_success "Cleaned up residual packages"

# Reinstall PHP and MySQL extensions
print_header "Installing PHP MySQL Extensions"

# Install core PHP packages for version 8.3
apt-get install -y php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-common php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-cli
check_success "Installed core PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION packages"

# Install MySQL extensions
apt-get install -y php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-mysql
check_success "Installed PHP MySQL extension" "exit"

# Make sure PHP dev package is installed (needed for some extensions)
apt-get install -y php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-dev
check_success "Installed PHP development package"

# Enable the extensions
print_header "Enabling MySQL Extensions"

if command -v phpenmod > /dev/null; then
    phpenmod -v $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION mysqlnd
    phpenmod -v $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION mysqli
    phpenmod -v $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION pdo_mysql
    check_success "Enabled MySQL extensions using phpenmod"
else
    echo -e "${YELLOW}phpenmod not found. Enabling extensions manually...${NC}"
    
    # Find all CLI/FPM/etc PHP configuration directories
    PHP_CONF_DIRS=$(find /etc/php/$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION/ -type d -name "conf.d" 2>/dev/null)
    
    for CONF_DIR in $PHP_CONF_DIRS; do
        echo -e "${BLUE}Enabling extensions in $CONF_DIR...${NC}"
        
        # Create extension configuration files if they don't exist
        echo "extension=mysqlnd.so" > $CONF_DIR/20-mysqlnd.ini
        echo "extension=mysqli.so" > $CONF_DIR/30-mysqli.ini
        echo "extension=pdo_mysql.so" > $CONF_DIR/40-pdo_mysql.ini
        
        check_success "Created extension configuration in $CONF_DIR"
    done
fi

# Restart services
print_header "Restarting Services"

# List of services to restart
SERVICES=("apache2" "nginx" "php$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION-fpm")

for SERVICE in "${SERVICES[@]}"; do
    if systemctl list-unit-files | grep -q "$SERVICE"; then
        echo -e "${YELLOW}Restarting $SERVICE...${NC}"
        systemctl restart $SERVICE
        check_success "Restarted $SERVICE"
    fi
done

# Verify installation
print_header "Verifying Installation"

# Check if extension files exist
MYSQLND_PATH="/usr/lib/php/$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION/mysqlnd.so"
MYSQLI_PATH="/usr/lib/php/$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION/mysqli.so"
PDO_MYSQL_PATH="/usr/lib/php/$PHP_MAJOR_VERSION.$PHP_MINOR_VERSION/pdo_mysql.so"

if [ -f "$MYSQLND_PATH" ]; then
    echo -e "${GREEN}✓ mysqlnd.so exists at: $MYSQLND_PATH${NC}"
else
    echo -e "${RED}✗ mysqlnd.so not found at expected location!${NC}"
    # Search for it elsewhere
    FOUND_PATH=$(find /usr -name "mysqlnd.so" 2>/dev/null | head -n 1)
    if [ ! -z "$FOUND_PATH" ]; then
        echo -e "${YELLOW}Found it at: $FOUND_PATH${NC}"
        echo -e "Creating symlink..."
        mkdir -p $(dirname "$MYSQLND_PATH")
        ln -sf "$FOUND_PATH" "$MYSQLND_PATH"
        check_success "Created symlink for mysqlnd.so"
    else
        echo -e "${RED}Could not find mysqlnd.so anywhere!${NC}"
    fi
fi

if [ -f "$PDO_MYSQL_PATH" ]; then
    echo -e "${GREEN}✓ pdo_mysql.so exists at: $PDO_MYSQL_PATH${NC}"
else
    echo -e "${RED}✗ pdo_mysql.so not found at expected location!${NC}"
    # Search for it elsewhere
    FOUND_PATH=$(find /usr -name "pdo_mysql.so" 2>/dev/null | head -n 1)
    if [ ! -z "$FOUND_PATH" ]; then
        echo -e "${YELLOW}Found it at: $FOUND_PATH${NC}"
        echo -e "Creating symlink..."
        mkdir -p $(dirname "$PDO_MYSQL_PATH")
        ln -sf "$FOUND_PATH" "$PDO_MYSQL_PATH"
        check_success "Created symlink for pdo_mysql.so"
    else
        echo -e "${RED}Could not find pdo_mysql.so anywhere!${NC}"
    fi
fi

# Check if PHP can see the extensions now
echo -e "\n${BLUE}Checking if PHP can see the MySQL extensions:${NC}"
php -m | grep -i mysql
check_success "Found MySQL-related modules in PHP"

# Test database connectivity
print_header "Testing Database Connectivity"

# Create a test PHP file
TEST_FILE=$(mktemp)
cat > $TEST_FILE << EOF
<?php
echo "Testing MySQL Connectivity\n";
try {
    \$pdo = new PDO('mysql:host=localhost', 'root', 'MO3848seven_36');
    echo "SUCCESS: Connected to MySQL server\n";
    \$version = \$pdo->query('SELECT VERSION()')->fetchColumn();
    echo "MySQL Version: " . \$version . "\n";
} catch (PDOException \$e) {
    echo "ERROR: " . \$e->getMessage() . "\n";
}
EOF

echo -e "${YELLOW}Running test script...${NC}"
php $TEST_FILE
rm $TEST_FILE

echo -e "\n${BLUE}Testing Symfony database creation:${NC}"
cd $(dirname $(dirname $0))  # Go to project root
php bin/console doctrine:database:create --if-not-exists
check_success "Symfony database creation test"

print_header "Installation Complete"

echo -e "${GREEN}${BOLD}PHP MySQL extensions have been reinstalled.${NC}"
echo -e "If everything looks good, you should now be able to use MySQL with your Symfony project."
echo -e "\n${YELLOW}If you still have issues, check the MySQL server configuration and credentials.${NC}"
