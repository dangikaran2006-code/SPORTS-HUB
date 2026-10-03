-- -----------------------------------------------------------------------------
-- SportsHub - Step 7 Database Migration: Matches & Fixtures Schema Expansion
-- -----------------------------------------------------------------------------

ALTER TABLE `matches`
  ADD COLUMN `round_name` VARCHAR(100) DEFAULT 'League' AFTER `status`,
  ADD COLUMN `round_number` INT UNSIGNED DEFAULT 1 AFTER `round_name`,
  ADD COLUMN `match_number` INT UNSIGNED DEFAULT 1 AFTER `round_number`,
  ADD COLUMN `scheduled_start` DATETIME DEFAULT NULL AFTER `match_number`,
  ADD COLUMN `scheduled_end` DATETIME DEFAULT NULL AFTER `scheduled_start`,
  ADD COLUMN `postponement_reason` TEXT DEFAULT NULL AFTER `scheduled_end`,
  ADD COLUMN `cancellation_reason` TEXT DEFAULT NULL AFTER `postponement_reason`;

-- Indexes for Conflict Detection Performance
ALTER TABLE `matches`
  ADD INDEX `idx_matches_venue_time` (`venue_id`, `scheduled_date`, `scheduled_time`),
  ADD INDEX `idx_matches_official_time` (`official_id`, `scheduled_date`, `scheduled_time`),
  ADD INDEX `idx_matches_team_a_time` (`team_a_id`, `scheduled_date`, `scheduled_time`),
  ADD INDEX `idx_matches_team_b_time` (`team_b_id`, `scheduled_date`, `scheduled_time`);
