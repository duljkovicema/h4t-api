-- Add first_protector_name column to users table
-- This column stores the custom name that users want to be remembered as first protector

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS first_protector_name VARCHAR(255) NULL 
COMMENT 'Custom name for first protector display. If NULL or empty, nickname will be used instead.';

