#!/bin/bash

# Database Helper Script
# This script helps with common database operations

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Get project directory
PROJECT_DIR="$(dirname "$(dirname "$(readlink -f "$0")")")"

# Database helper functions
function show_help {
    echo -e "${BLUE}${BOLD}Symfony Database Helper${NC}"
    echo -e "Usage: $0 [command]"
    echo -e ""
    echo -e "Commands:"
    echo -e "  ${BOLD}create${NC}      - Create the database (if not exists)"
    echo -e "  ${BOLD}drop${NC}        - Drop the database"
    echo -e "  ${BOLD}reset${NC}       - Drop and recreate the database"
    echo -e "  ${BOLD}schema${NC}      - Create/update database schema"
    echo -e "  ${BOLD}fixtures${NC}    - Load data fixtures"
    echo -e "  ${BOLD}migrations${NC}  - Run database migrations"
    echo -e "  ${BOLD}status${NC}      - Show migration status"
    echo -e "  ${BOLD}help${NC}        - Show this help message"
    echo -e ""
}

function run_command {
    echo -e "${YELLOW}Running: $1${NC}"
    cd "$PROJECT_DIR"
    eval "$1"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Command completed successfully${NC}"
        return 0
    else
        echo -e "${RED}✗ Command failed with error code $?${NC}"
        return 1
    fi
}

# Main script logic
if [ $# -eq 0 ]; then
    show_help
    exit 1
fi

case "$1" in
    create)
        run_command "php bin/console doctrine:database:create --if-not-exists"
        ;;
    drop)
        run_command "php bin/console doctrine:database:drop --force --if-exists"
        ;;
    reset)
        run_command "php bin/console doctrine:database:drop --force --if-exists"
        run_command "php bin/console doctrine:database:create"
        ;;
    schema)
        run_command "php bin/console doctrine:schema:update --force"
        ;;
    fixtures)
        if [ -f "$PROJECT_DIR/src/DataFixtures/AppFixtures.php" ]; then
            run_command "php bin/console doctrine:fixtures:load --no-interaction"
        else
            echo -e "${YELLOW}No fixtures found. Install fixtures first:${NC}"
            echo -e "composer require --dev doctrine/doctrine-fixtures-bundle"
        fi
        ;;
    migrations)
        if [ -d "$PROJECT_DIR/migrations" ]; then
            run_command "php bin/console doctrine:migrations:migrate --no-interaction"
        else
            echo -e "${YELLOW}No migrations found. Create a migration first:${NC}"
            echo -e "php bin/console doctrine:migrations:diff"
        fi
        ;;
    status)
        run_command "php bin/console doctrine:schema:validate"
        if [ -d "$PROJECT_DIR/migrations" ]; then
            run_command "php bin/console doctrine:migrations:status"
        fi
        ;;
    help)
        show_help
        ;;
    *)
        echo -e "${RED}Unknown command: $1${NC}"
        show_help
        exit 1
        ;;
esac

exit 0
