-- Check if meal_type column exists, and create it if it doesn't
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
                       WHERE table_name = 'menu' 
                       AND column_name = 'meal_type'
                       AND table_schema = DATABASE());

SET @create_column = CONCAT('ALTER TABLE menu ADD COLUMN meal_type VARCHAR(255) DEFAULT "main" ', 
                            IF(@column_exists = 0, '', ''));

-- Only create the column if it doesn't exist
SET @sql = IF(@column_exists = 0, @create_column, 'SELECT "Column already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing NULL values to 'main'
UPDATE menu SET meal_type = 'main' WHERE meal_type IS NULL;

-- Now add NOT NULL constraint if column exists
SET @add_not_null = 'ALTER TABLE menu MODIFY COLUMN meal_type VARCHAR(255) NOT NULL DEFAULT "main"';

SET @sql = IF(@column_exists > 0, @add_not_null, 'SELECT "Column was just created with correct constraints"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show status message
SELECT CONCAT('Menu table updated successfully. meal_type column is now ', 
              IF(@column_exists = 0, 'created', 'updated'), 
              ' with NOT NULL and DEFAULT constraints.') AS 'Result';
