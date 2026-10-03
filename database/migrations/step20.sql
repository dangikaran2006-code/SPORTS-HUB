-- Step 20: Final Schema Integrity Fix - Venues Location Column

ALTER TABLE `venues` ADD COLUMN IF NOT EXISTS `location` VARCHAR(255) NULL AFTER `name`;
UPDATE `venues` SET `location` = CONCAT_WS(', ', address, city) WHERE location IS NULL OR location = '';
