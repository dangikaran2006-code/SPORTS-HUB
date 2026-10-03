-- =============================================================================
-- SportsHub - Step 5 Database Migration Script
-- Adds `max_teams` and `registration_deadline` to `tournaments` table
-- =============================================================================

USE `sportshub`;

-- Add missing columns if they don't already exist
ALTER TABLE `tournaments` 
  ADD COLUMN IF NOT EXISTS `max_teams` INT UNSIGNED DEFAULT 16 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `registration_deadline` DATE DEFAULT NULL AFTER `max_teams`;
