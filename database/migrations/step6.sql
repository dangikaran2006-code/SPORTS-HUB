-- =============================================================================
-- SportsHub - Step 6 Database Migration Script
-- Adds `description` to `teams` table and updates `players.status`
-- =============================================================================

USE `sportshub`;

-- 1. Ensure `description` column exists on `teams`
ALTER TABLE `teams` 
  ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL AFTER `manager_id`;

-- 2. Modify `players.status` to support enum/varchar status strings: active, inactive, injured, suspended
ALTER TABLE `players`
  MODIFY COLUMN `status` VARCHAR(20) DEFAULT 'active';
