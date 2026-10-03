-- =============================================================================
-- SportsHub - Multi-Sport Tournament Management Platform
-- Normalized Relational MySQL Database Schema (`sportshub`)
-- Engine: InnoDB | Character Set: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `sportshub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sportshub`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `team_statistics`;
DROP TABLE IF EXISTS `player_statistics`;
DROP TABLE IF EXISTS `points_table`;
DROP TABLE IF EXISTS `scores`;
DROP TABLE IF EXISTS `match_events`;
DROP TABLE IF EXISTS `matches`;
DROP TABLE IF EXISTS `officials`;
DROP TABLE IF EXISTS `players`;
DROP TABLE IF EXISTS `tournament_teams`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `tournaments`;
DROP TABLE IF EXISTS `venues`;
DROP TABLE IF EXISTS `sports`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. USERS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'organizer', 'scorer', 'official', 'team_manager', 'player') NOT NULL DEFAULT 'player',
  `profile_image` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. SPORTS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `sports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(60) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. VENUES TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `venues` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `capacity` INT UNSIGNED DEFAULT 0,
  `sport_type` VARCHAR(100) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TOURNAMENTS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `tournaments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,
  `sport_id` INT UNSIGNED NOT NULL,
  `logo` VARCHAR(255) DEFAULT 'assets/images/default-tournament.png',
  `description` TEXT DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `venue_id` INT UNSIGNED DEFAULT NULL,
  `format` ENUM('league', 'knockout', 'round_robin', 'group_knockout') NOT NULL DEFAULT 'league',
  `status` ENUM('draft', 'upcoming', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tournaments_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_tournaments_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tournaments_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_tournaments_sport` (`sport_id`),
  INDEX `idx_tournaments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TEAMS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `teams` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `short_name` VARCHAR(10) NOT NULL,
  `logo` VARCHAR(255) DEFAULT 'assets/images/default-team.png',
  `sport_id` INT UNSIGNED NOT NULL,
  `captain_id` INT UNSIGNED DEFAULT NULL,
  `manager_id` INT UNSIGNED DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_teams_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE RESTRICT,
  INDEX `idx_teams_sport` (`sport_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TOURNAMENT_TEAMS TABLE (Junction Table)
-- -----------------------------------------------------------------------------
CREATE TABLE `tournament_teams` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `group_name` VARCHAR(10) DEFAULT 'A',
  `seed_number` INT UNSIGNED DEFAULT NULL,
  `registration_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
  `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tt_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_tournament_team` (`tournament_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. PLAYERS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `players` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT 'assets/images/default-player.png',
  `date_of_birth` DATE DEFAULT NULL,
  `jersey_number` INT DEFAULT NULL,
  `sport_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED DEFAULT NULL,
  `position` VARCHAR(50) DEFAULT 'Player',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_players_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_players_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
  INDEX `idx_players_team` (`team_id`),
  INDEX `idx_players_sport` (`sport_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. OFFICIALS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `officials` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('referee', 'umpire', 'scorer', 'judge', 'official') DEFAULT 'official',
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `sport_id` INT UNSIGNED NOT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_officials_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_officials_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. MATCHES TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `matches` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT UNSIGNED NOT NULL,
  `sport_id` INT UNSIGNED NOT NULL,
  `team_a_id` INT UNSIGNED NOT NULL,
  `team_b_id` INT UNSIGNED NOT NULL,
  `venue_id` INT UNSIGNED DEFAULT NULL,
  `official_id` INT UNSIGNED DEFAULT NULL,
  `scheduled_date` DATE NOT NULL,
  `scheduled_time` TIME NOT NULL,
  `status` ENUM('scheduled', 'live', 'completed', 'postponed', 'cancelled') DEFAULT 'scheduled',
  `winner_team_id` INT UNSIGNED DEFAULT NULL,
  `result_summary` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_matches_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_matches_team_a` FOREIGN KEY (`team_a_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_team_b` FOREIGN KEY (`team_b_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_winner` FOREIGN KEY (`winner_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_official` FOREIGN KEY (`official_id`) REFERENCES `officials`(`id`) ON DELETE SET NULL,
  INDEX `idx_matches_tournament` (`tournament_id`),
  INDEX `idx_matches_scheduled_date` (`scheduled_date`),
  INDEX `idx_matches_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. MATCH_EVENTS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `match_events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED DEFAULT NULL,
  `player_id` INT UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(50) NOT NULL, -- goal, run, wicket, foul, card, point, substitution
  `event_value` INT DEFAULT 1,
  `event_data` TEXT DEFAULT NULL,
  `event_time` VARCHAR(20) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_events_match` FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_events_player` FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. SCORES TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `scores` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `score` INT DEFAULT 0,
  `period` VARCHAR(50) DEFAULT '1', -- innings, full_time, half
  `period_label` VARCHAR(50) DEFAULT NULL,
  `metadata` TEXT DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_scores_match` FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scores_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_match_team_score` (`match_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. POINTS_TABLE TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `points_table` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `played` INT DEFAULT 0,
  `won` INT DEFAULT 0,
  `lost` INT DEFAULT 0,
  `drawn` INT DEFAULT 0,
  `points` INT DEFAULT 0,
  `score_for` INT DEFAULT 0,
  `score_against` INT DEFAULT 0,
  `score_difference` INT DEFAULT 0,
  `net_run_rate` DECIMAL(6,3) DEFAULT 0.000,
  `position` INT DEFAULT 1,
  `form` VARCHAR(20) DEFAULT '',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pt_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_tournament_team_points` (`tournament_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. PLAYER_STATISTICS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `player_statistics` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT UNSIGNED NOT NULL,
  `match_id` INT UNSIGNED DEFAULT NULL,
  `player_id` INT UNSIGNED NOT NULL,
  `stat_type` VARCHAR(50) NOT NULL, -- runs, goals, assists, wickets, points, aces, kills
  `stat_value` DECIMAL(10,2) DEFAULT 0.00,
  `metadata` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pstats_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pstats_match` FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pstats_player` FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. TEAM_STATISTICS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `team_statistics` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `stat_type` VARCHAR(50) NOT NULL,
  `stat_value` DECIMAL(10,2) DEFAULT 0.00,
  `metadata` TEXT DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tstats_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tstats_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. NOTIFICATIONS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. AUDIT_LOGS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
