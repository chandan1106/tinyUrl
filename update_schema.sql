-- Add columns for Google authentication if they don't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS display_name VARCHAR(255) DEFAULT '';
ALTER TABLE users ADD COLUMN IF NOT EXISTS photo_url TEXT;

-- If your MySQL version doesn't support IF NOT EXISTS in ALTER TABLE, use this alternative approach:
-- First check if the column exists
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'display_name';

-- Add the column if it doesn't exist
SET @query = IF(@columnExists = 0, 'ALTER TABLE users ADD COLUMN display_name VARCHAR(255) DEFAULT \'\'', 'SELECT \'Column exists\'');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if photo_url column exists
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'photo_url';

-- Add the column if it doesn't exist
SET @query = IF(@columnExists = 0, 'ALTER TABLE users ADD COLUMN photo_url TEXT', 'SELECT \'Column exists\'');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;