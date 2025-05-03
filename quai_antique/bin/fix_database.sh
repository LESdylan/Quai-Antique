#!/bin/bash

echo "=== Quai Antique Database Fix Tool ==="
echo

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get project directory
PROJECT_DIR=$(dirname $(dirname $0))
cd "$PROJECT_DIR"

# Helper function to execute SQL queries
execute_sql() {
    local sql="$1"
    local message="$2"
    local db_name="$3"
    local db_user="$4"
    local db_pass="$5"
    local db_host="$6"
    
    echo -e "${YELLOW}Executing SQL: ${message}${NC}"
    mysql -h"$db_host" -u"$db_user" -p"$db_pass" "$db_name" -e "$sql" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ SQL executed successfully${NC}"
        return 0
    else
        echo -e "${RED}✗ SQL execution failed${NC}"
        return 1
    fi
}

# Helper function to check if a column exists
column_exists() {
    local table="$1"
    local column="$2"
    local db_name="$3"
    local db_user="$4"
    local db_pass="$5"
    local db_host="$6"
    
    local sql="SELECT COUNT(*) FROM information_schema.columns 
            WHERE table_schema = '$db_name' 
              AND table_name = '$table' 
              AND column_name = '$column';"
    
    local result=$(mysql -h"$db_host" -u"$db_user" -p"$db_pass" -N -s -e "$sql" 2>/dev/null)
    
    if [ "$result" -eq "1" ]; then
        return 0
    else
        return 1
    fi
}

echo -e "${YELLOW}Step 1: Validating schema with our custom command${NC}"
php bin/console app:schema:validate
if [ $? -ne 0 ]; then
    echo -e "${RED}Schema validation found issues. We'll attempt to fix them.${NC}"
else
    echo -e "${GREEN}Schema validation passed! Your database schema appears to be in good shape.${NC}"
    exit 0
fi

echo -e "\n${YELLOW}Step 2: Running safe schema update${NC}"
php bin/console app:schema:update --force
if [ $? -ne 0 ]; then
    echo -e "${RED}Safe schema update failed. Trying direct SQL approach...${NC}"
else
    echo -e "${GREEN}Schema update completed successfully!${NC}"
    exit 0
fi

echo -e "\n${YELLOW}Step 3: Attempting to fix known schema validation errors${NC}"
# Fix the problematic table constraints manually
DB_URL=$(grep DATABASE_URL .env.local 2>/dev/null || grep DATABASE_URL .env)
DB_NAME=$(echo $DB_URL | grep -oP 'mysql://[^:]+:[^@]*@[^:]+(?::[^/]+)?/\K[^?]*')

if [ -z "$DB_NAME" ]; then
    echo -e "${RED}Could not determine database name. Please update your schema manually.${NC}"
    exit 1
fi

# Extract database credentials from DATABASE_URL
DB_USER=$(echo $DB_URL | grep -oP 'mysql://\K[^:]+')
DB_PASS=$(echo $DB_URL | grep -oP 'mysql://[^:]+:\K[^@]+')
DB_HOST=$(echo $DB_URL | grep -oP 'mysql://[^:]+:[^@]+@\K[^:/]+')
DB_PORT=$(echo $DB_URL | grep -oP 'mysql://[^:]+:[^@]+@[^:/]+:\K[^/]+' || echo "3306")

echo "Using database: $DB_NAME on $DB_HOST"

echo -e "\n${YELLOW}Step 4: Checking reservation table structure${NC}"

# Check if reservation table exists
TABLE_CHECK="SELECT COUNT(*) FROM information_schema.tables 
             WHERE table_schema = '$DB_NAME' AND table_name = 'reservation';"
TABLE_EXISTS=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -N -s -e "$TABLE_CHECK" 2>/dev/null)

if [ "$TABLE_EXISTS" -eq "0" ]; then
    echo -e "${RED}Reservation table does not exist. Creating it...${NC}"
    
    CREATE_TABLE_SQL="CREATE TABLE `reservation` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `date` DATETIME NOT NULL,
        `guest_count` INT NOT NULL,
        `last_name` VARCHAR(64) NOT NULL,
        `first_name` VARCHAR(64) NULL,
        `email` VARCHAR(180) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `notes` TEXT NULL,
        `allergies` TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `user_id` INT NULL
    );"
    
    execute_sql "$CREATE_TABLE_SQL" "Creating reservation table" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"
    
    # Add foreign key if user table exists
    USER_TABLE_EXISTS=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -N -s -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'user';" 2>/dev/null)
    
    if [ "$USER_TABLE_EXISTS" -eq "1" ]; then
        FK_SQL="ALTER TABLE `reservation` 
                ADD CONSTRAINT `FK_reservation_user` 
                FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;"
        
        execute_sql "$FK_SQL" "Adding foreign key for user_id" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"
    fi
else
    echo -e "${GREEN}Reservation table exists. Checking for missing columns...${NC}"
    
    # List of columns to check
    declare -a COLUMNS=(
        "last_name:VARCHAR(64) NOT NULL"
        "first_name:VARCHAR(64) NULL"
        "email:VARCHAR(180) NOT NULL"
        "phone:VARCHAR(20) NOT NULL"
        "status:VARCHAR(20) NOT NULL DEFAULT 'pending'"
        "notes:TEXT NULL"
        "allergies:TEXT NULL"
        "created_at:DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
        "user_id:INT NULL"
    )
    
    # Check each column
    for COL in "${COLUMNS[@]}"; do
        COLUMN_NAME="${COL%%:*}"
        COLUMN_DEF="${COL#*:}"
        
        if ! column_exists "reservation" "$COLUMN_NAME" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"; then
            echo -e "${YELLOW}Missing column '$COLUMN_NAME' in reservation table${NC}"
            
            ADD_COLUMN_SQL="ALTER TABLE `reservation` ADD COLUMN `$COLUMN_NAME` $COLUMN_DEF;"
            execute_sql "$ADD_COLUMN_SQL" "Adding column $COLUMN_NAME" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"
        else
            echo -e "${GREEN}Column '$COLUMN_NAME' exists in reservation table${NC}"
        fi
    done
    
    # Check if user_id foreign key exists
    FK_CHECK="SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
              WHERE TABLE_SCHEMA = '$DB_NAME' 
                AND TABLE_NAME = 'reservation' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME = 'user';"
                
    FK_EXISTS=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -N -s -e "$FK_CHECK" 2>/dev/null)
    
    if [ "$FK_EXISTS" -eq "0" ] && column_exists "reservation" "user_id" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"; then
        echo -e "${YELLOW}Foreign key for user_id is missing${NC}"
        
        # Check if we can add the foreign key (user table exists)
        USER_TABLE_EXISTS=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -N -s -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'user';" 2>/dev/null)
        
        if [ "$USER_TABLE_EXISTS" -eq "1" ]; then
            FK_SQL="ALTER TABLE `reservation` 
                    ADD CONSTRAINT `FK_reservation_user` 
                    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;"
            
            execute_sql "$FK_SQL" "Adding foreign key for user_id" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"
        else
            echo -e "${YELLOW}User table doesn't exist, skipping foreign key creation${NC}"
        fi
    fi
fi

echo -e "\n${YELLOW}Step 5: Verifying fix with Symfony validation${NC}"
php bin/console app:schema:validate --skip-mapping

echo -e "\n${GREEN}Database fix attempts completed.${NC}"
echo -e "To verify your database, run: php bin/console app:schema:validate"
echo -e "If issues persist, you may need to manually fix the database structure."
