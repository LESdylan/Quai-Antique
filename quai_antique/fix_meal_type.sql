-- Check if meal_type column exists
SET @column_exists = (SELECT COUNT(*) 
                     FROM information_schema.columns 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'menu' 
                     AND column_name = 'meal_type');

-- Create the column if it doesn't exist
SET @create_stmt = IF(@column_exists = 0, 
                     'ALTER TABLE menu ADD COLUMN meal_type VARCHAR(255) DEFAULT "main" NOT NULL',
                     'SELECT "Column already exists"');
PREPARE stmt FROM @create_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records with NULL values
UPDATE menu SET meal_type = 'main' WHERE meal_type IS NULL OR meal_type = '';

-- Apply NOT NULL constraint
SET @alter_stmt = 'ALTER TABLE menu MODIFY COLUMN meal_type VARCHAR(255) NOT NULL DEFAULT "main"';
PREPARE stmt FROM @alter_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show a status message
SELECT CONCAT('Fixed menu table: ',
             (SELECT COUNT(*) FROM menu), ' total records, ',
             (SELECT COUNT(*) FROM menu WHERE meal_type = "main"), ' with meal_type="main"') AS Result;
