-- ==================================================
-- SportsHub Database Dump
-- Created: 2026-10-03 18:12:08
-- Database: sportshub
-- Created By: Automated Test Suite
-- Application Version: 1.0.0
-- ==================================================

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------
-- Table structure for `announcements`
-- --------------------------------------------------
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sport` varchar(100) DEFAULT 'General',
  `target_audience` varchar(50) DEFAULT 'Everyone',
  `department` varchar(100) DEFAULT NULL,
  `priority` enum('Normal','Important','Urgent') DEFAULT 'Normal',
  `status` varchar(50) DEFAULT 'Published',
  `publish_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `announcements` (4 records)
INSERT INTO `announcements` (`id`, `title`, `message`, `sport`, `target_audience`, `department`, `priority`, `status`, `publish_date`, `created_at`) VALUES
('1', '🏏 Cricket Championship Final Rescheduled Time', 'The Grand Cricket Final between Computer Engineering (CSE) and Mechanical Engineering (ME) will commence at 4:00 PM IST today at the Main Ground.', 'Cricket', 'Everyone', NULL, 'Urgent', 'Published', NULL, '2026-10-03 20:54:32'),
('2', '🏸 Badminton Singles Venue Allocation Update', 'Due to rain forecast, all Badminton Men Singles & Mixed Doubles matches have been shifted to Indoor Sports Complex Court 1 & Court 2.', 'Badminton', 'Everyone', NULL, 'Important', 'Published', NULL, '2026-10-03 20:54:32'),
('3', '🏆 Overall Department Points Calculation Rules Published', 'The Sports Board has published the official Point Matrix for AY 2025-2026. Team Sports Gold: 10pts, Silver: 7pts, Bronze: 5pts.', 'General', 'Everyone', NULL, 'Normal', 'Published', NULL, '2026-10-03 20:54:32'),
('4', 'Test Championship Bulletin 1791042444', 'Test message content for Step 22 verification.', 'Cricket', 'Everyone', 'All', 'Urgent', 'Published', NULL, '2026-10-03 17:47:24');

-- --------------------------------------------------
-- Table structure for `audit_logs`
-- --------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `audit_logs` (1 records)
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `created_at`) VALUES
('1', '1', 'DATABASE_INIT', 'SYSTEM', '1', 'Initial Database Schema & Seed Data Loaded Successfully', '127.0.0.1', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `departments`
-- --------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `short_code` varchar(20) NOT NULL,
  `color_code` varchar(20) DEFAULT '#00e676',
  `logo` varchar(255) DEFAULT 'assets/images/dept-cse.png',
  `contact_person` varchar(150) DEFAULT '',
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `departments` (6 records)
INSERT INTO `departments` (`id`, `name`, `short_code`, `color_code`, `logo`, `contact_person`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Computer Engineering / BCA', 'CSE', '#00e676', 'assets/images/dept-cse.png', 'Dr. Alan Turing', '1', '2026-10-03 20:54:32', '2026-10-03 20:54:32'),
('2', 'Mechanical Engineering', 'ME', '#3b82f6', 'assets/images/dept-me.png', 'Prof. Nikola Tesla', '1', '2026-10-03 20:54:32', '2026-10-03 20:54:32'),
('3', 'Civil Engineering', 'CE', '#f59e0b', 'assets/images/dept-ce.png', 'Er. Isambard Brunel', '1', '2026-10-03 20:54:32', '2026-10-03 20:54:32'),
('4', 'Electrical & Electronics', 'EEE', '#ec4899', 'assets/images/dept-eee.png', 'Dr. Michael Faraday', '1', '2026-10-03 20:54:32', '2026-10-03 20:54:32'),
('5', 'Information Technology', 'IT', '#8b5cf6', 'assets/images/dept-it.png', 'Prof. Tim Berners-Lee', '1', '2026-10-03 20:54:32', '2026-10-03 20:54:32'),
('6', 'Electronics & Communication', 'ECE', '#06b6d4', 'assets/images/dept-ece.png', 'Dr. Guglielmo Marconi', '1', '2026-10-03 20:54:32', '2026-10-03 20:54:32');

-- --------------------------------------------------
-- Table structure for `match_events`
-- --------------------------------------------------
DROP TABLE IF EXISTS `match_events`;
CREATE TABLE `match_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned DEFAULT NULL,
  `player_id` int(10) unsigned DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_value` int(11) DEFAULT 1,
  `event_data` text DEFAULT NULL,
  `event_time` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_events_match` (`match_id`),
  KEY `fk_events_team` (`team_id`),
  KEY `fk_events_player` (`player_id`),
  CONSTRAINT `fk_events_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_events_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `match_events` (2 records)
INSERT INTO `match_events` (`id`, `match_id`, `team_id`, `player_id`, `event_type`, `event_value`, `event_data`, `event_time`, `created_at`) VALUES
('1', '1', '1', '1', 'run', '6', 'Rohit Sharma hits 6 over deep mid-wicket!', '17.2 overs', '2026-10-03 20:46:22'),
('2', '3', '5', '5', 'goal', '1', 'Sunil Chhetri headers into top corner!', '24\'', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `matches`
-- --------------------------------------------------
DROP TABLE IF EXISTS `matches`;
CREATE TABLE `matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tournament_id` int(10) unsigned NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `team_a_id` int(10) unsigned NOT NULL,
  `team_b_id` int(10) unsigned NOT NULL,
  `venue_id` int(10) unsigned DEFAULT NULL,
  `official_id` int(10) unsigned DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `status` enum('scheduled','live','completed','postponed','cancelled') DEFAULT 'scheduled',
  `winner_team_id` int(10) unsigned DEFAULT NULL,
  `result_summary` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_matches_sport` (`sport_id`),
  KEY `fk_matches_team_a` (`team_a_id`),
  KEY `fk_matches_team_b` (`team_b_id`),
  KEY `fk_matches_venue` (`venue_id`),
  KEY `fk_matches_winner` (`winner_team_id`),
  KEY `fk_matches_official` (`official_id`),
  KEY `idx_matches_tournament` (`tournament_id`),
  KEY `idx_matches_scheduled_date` (`scheduled_date`),
  KEY `idx_matches_status` (`status`),
  CONSTRAINT `fk_matches_official` FOREIGN KEY (`official_id`) REFERENCES `officials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  CONSTRAINT `fk_matches_team_a` FOREIGN KEY (`team_a_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_team_b` FOREIGN KEY (`team_b_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_winner` FOREIGN KEY (`winner_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `matches` (4 records)
INSERT INTO `matches` (`id`, `tournament_id`, `sport_id`, `team_a_id`, `team_b_id`, `venue_id`, `official_id`, `scheduled_date`, `scheduled_time`, `status`, `winner_team_id`, `result_summary`, `created_at`, `updated_at`) VALUES
('1', '1', '1', '1', '2', '1', '1', '2026-10-03', '19:30:00', 'live', NULL, 'Royal Strikers need 12 runs off 8 balls', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('2', '1', '1', '3', '4', '1', '3', '2026-10-02', '16:00:00', 'live', NULL, 'Rising Panthers won by 4 wickets', '2026-10-03 20:46:22', '2026-10-03 21:28:04'),
('3', '2', '2', '5', '6', '2', '2', '2026-10-03', '20:00:00', 'live', NULL, 'Apex FC leads 2 - 1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('4', '3', '3', '7', '8', '3', '1', '2026-10-10', '18:00:00', 'scheduled', NULL, 'Scheduled kickoff at 6:00 PM', '2026-10-03 20:46:22', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'system',
  `link_url` varchar(255) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notifications_user` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `notifications` (26 records)
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link_url`, `priority`, `is_read`, `created_at`) VALUES
('1', NULL, 'Test Broadcast Alert', 'Broadcast notification message.', 'ANNOUNCEMENT', 'public/announcements.php', 'Urgent', '1', '2026-10-03 17:47:24'),
('2', NULL, 'Test Broadcast Alert', 'Broadcast notification message.', 'ANNOUNCEMENT', 'public/announcements.php', 'Urgent', '1', '2026-10-03 17:47:51'),
('3', NULL, '📅 New Fixture Scheduled: Badminton — Royal Strikers vs Thunder Warriors', 'Match scheduled for Oct 05, 2026 at 04:00 PM.', 'EVENT_CREATED', 'public/match-detail.php?id=7', 'Normal', '1', '2026-10-03 17:47:51'),
('4', NULL, '🕒 Match Rescheduled: Badminton — Royal Strikers vs Thunder Warriors', 'The match is now rescheduled to Oct 06, 2026 at 05:30 PM.', 'EVENT_RESCHEDULED', 'public/match-detail.php?id=7', 'Important', '1', '2026-10-03 17:47:51'),
('5', NULL, '🔴 LIVE NOW: Badminton — Royal Strikers vs Thunder Warriors', 'The Badminton match between Royal Strikers and Thunder Warriors is now LIVE!', 'EVENT_STARTED', 'public/live.php?match_id=7', 'Important', '1', '2026-10-03 17:47:51'),
('6', NULL, '🏁 Match Completed: Badminton — Royal Strikers vs Thunder Warriors', 'Badminton — Royal Strikers vs Thunder Warriors has finished. Result: Team A won by 10 runs', 'EVENT_FINISHED', 'public/match-detail.php?id=7', 'Normal', '1', '2026-10-03 17:47:51'),
('7', NULL, '🏆 Official Result Published: Badminton — Royal Strikers vs Thunder Warriors', 'Official result published for Badminton — Royal Strikers vs Thunder Warriors. Result: Team A won by 10 runs', 'RESULT_PUBLISHED', 'public/results.php?match_id=7', 'Important', '1', '2026-10-03 17:47:51'),
('8', NULL, '📊 Championship Points Table Updated', 'The official standings and points table have been updated following recent match results.', 'POINTS_UPDATED', 'public/points-table.php', 'Normal', '1', '2026-10-03 17:47:51'),
('9', NULL, 'Test Broadcast Alert', 'Broadcast notification message.', 'ANNOUNCEMENT', 'public/announcements.php', 'Urgent', '1', '2026-10-03 17:48:37'),
('10', NULL, '📅 New Fixture Scheduled: Badminton — Royal Strikers vs Thunder Warriors', 'Match scheduled for Oct 05, 2026 at 04:00 PM.', 'EVENT_CREATED', 'public/match-detail.php?id=8', 'Normal', '1', '2026-10-03 17:48:37'),
('11', NULL, '🕒 Match Rescheduled: Badminton — Royal Strikers vs Thunder Warriors', 'The match is now rescheduled to Oct 06, 2026 at 05:30 PM.', 'EVENT_RESCHEDULED', 'public/match-detail.php?id=8', 'Important', '1', '2026-10-03 17:48:37'),
('12', NULL, '🔴 LIVE NOW: Badminton — Royal Strikers vs Thunder Warriors', 'The Badminton match between Royal Strikers and Thunder Warriors is now LIVE!', 'EVENT_STARTED', 'public/live.php?match_id=8', 'Important', '1', '2026-10-03 17:48:37'),
('13', NULL, '🏁 Match Completed: Badminton — Royal Strikers vs Thunder Warriors', 'Badminton — Royal Strikers vs Thunder Warriors has finished. Result: Team A won by 10 runs', 'EVENT_FINISHED', 'public/match-detail.php?id=8', 'Normal', '1', '2026-10-03 17:48:37'),
('14', NULL, '🏆 Official Result Published: Badminton — Royal Strikers vs Thunder Warriors', 'Official result published for Badminton — Royal Strikers vs Thunder Warriors. Result: Team A won by 10 runs', 'RESULT_PUBLISHED', 'public/results.php?match_id=8', 'Important', '1', '2026-10-03 17:48:37'),
('15', NULL, '📊 Championship Points Table Updated', 'The official standings and points table have been updated following recent match results.', 'POINTS_UPDATED', 'public/points-table.php', 'Normal', '1', '2026-10-03 17:48:37'),
('16', NULL, 'Test Broadcast Alert', 'Broadcast notification message.', 'ANNOUNCEMENT', 'public/announcements.php', 'Urgent', '1', '2026-10-03 17:49:00'),
('17', NULL, '📅 New Fixture Scheduled: Badminton — Royal Strikers vs Thunder Warriors', 'Match scheduled for Oct 05, 2026 at 04:00 PM.', 'EVENT_CREATED', 'public/match-detail.php?id=9', 'Normal', '1', '2026-10-03 17:49:00'),
('18', NULL, '🕒 Match Rescheduled: Badminton — Royal Strikers vs Thunder Warriors', 'The match is now rescheduled to Oct 06, 2026 at 05:30 PM.', 'EVENT_RESCHEDULED', 'public/match-detail.php?id=9', 'Important', '1', '2026-10-03 17:49:00'),
('19', NULL, '🔴 LIVE NOW: Badminton — Royal Strikers vs Thunder Warriors', 'The Badminton match between Royal Strikers and Thunder Warriors is now LIVE!', 'EVENT_STARTED', 'public/live.php?match_id=9', 'Important', '1', '2026-10-03 17:49:00'),
('20', NULL, '🏁 Match Completed: Badminton — Royal Strikers vs Thunder Warriors', 'Badminton — Royal Strikers vs Thunder Warriors has finished. Result: Team A won by 10 runs', 'EVENT_FINISHED', 'public/match-detail.php?id=9', 'Normal', '1', '2026-10-03 17:49:00'),
('21', NULL, '🏆 Official Result Published: Badminton — Royal Strikers vs Thunder Warriors', 'Official result published for Badminton — Royal Strikers vs Thunder Warriors. Result: Team A won by 10 runs', 'RESULT_PUBLISHED', 'public/results.php?match_id=9', 'Important', '1', '2026-10-03 17:49:00'),
('22', NULL, '📊 Championship Points Table Updated', 'The official standings and points table have been updated following recent match results.', 'POINTS_UPDATED', 'public/points-table.php', 'Normal', '1', '2026-10-03 17:49:00'),
('23', NULL, '🏆 Official Result Published: Cricket — Rising Panthers vs Coastal Kings', 'Official result published for Cricket — Rising Panthers vs Coastal Kings. Result: Rising Panthers won by 4 wickets', 'RESULT_PUBLISHED', 'public/results.php?match_id=2', 'Important', '0', '2026-10-03 17:57:57'),
('24', NULL, '📊 Championship Points Table Updated', 'The official standings and points table have been updated following recent match results.', 'POINTS_UPDATED', 'public/points-table.php', 'Normal', '0', '2026-10-03 17:57:57'),
('25', NULL, '⚠️ Official Result Updated: Cricket — Rising Panthers vs Coastal Kings', 'An official result correction has been posted for Cricket — Rising Panthers vs Coastal Kings. Match result re-opened for official score correction.', 'RESULT_CORRECTED', 'public/results.php?match_id=2', 'Urgent', '0', '2026-10-03 17:58:04'),
('26', NULL, '📊 Championship Points Table Updated', 'The official standings and points table have been updated following recent match results.', 'POINTS_UPDATED', 'public/points-table.php', 'Normal', '0', '2026-10-03 17:58:04');

-- --------------------------------------------------
-- Table structure for `officials`
-- --------------------------------------------------
DROP TABLE IF EXISTS `officials`;
CREATE TABLE `officials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('referee','umpire','scorer','judge','official') DEFAULT 'official',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_officials_user` (`user_id`),
  KEY `fk_officials_sport` (`sport_id`),
  CONSTRAINT `fk_officials_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  CONSTRAINT `fk_officials_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `officials` (3 records)
INSERT INTO `officials` (`id`, `user_id`, `name`, `role`, `phone`, `email`, `sport_id`, `status`, `created_at`) VALUES
('1', '3', 'Nitin Menon', 'scorer', NULL, 'scorer@sportshub.com', '1', '1', '2026-10-03 20:46:22'),
('2', '4', 'Pranjal Banerjee', 'referee', NULL, 'official@sportshub.com', '2', '1', '2026-10-03 20:46:22'),
('3', NULL, 'Kumar Dharmasena', 'umpire', NULL, 'kumar.d@referee.org', '1', '1', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `player_statistics`
-- --------------------------------------------------
DROP TABLE IF EXISTS `player_statistics`;
CREATE TABLE `player_statistics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tournament_id` int(10) unsigned NOT NULL,
  `match_id` int(10) unsigned DEFAULT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `stat_type` varchar(50) NOT NULL,
  `stat_value` decimal(10,2) DEFAULT 0.00,
  `metadata` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pstats_tournament` (`tournament_id`),
  KEY `fk_pstats_match` (`match_id`),
  KEY `fk_pstats_player` (`player_id`),
  CONSTRAINT `fk_pstats_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pstats_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pstats_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `player_statistics` (9 records)
INSERT INTO `player_statistics` (`id`, `tournament_id`, `match_id`, `player_id`, `stat_type`, `stat_value`, `metadata`, `created_at`) VALUES
('1', '1', NULL, '1', 'run', '10.00', NULL, '2026-10-03 21:08:16'),
('2', '1', NULL, '2', 'wicket', '1.00', NULL, '2026-10-03 21:08:16'),
('3', '1', NULL, '1', 'run', '10.00', NULL, '2026-10-03 21:08:16'),
('4', '1', NULL, '2', 'wicket', '1.00', NULL, '2026-10-03 21:08:16'),
('5', '1', NULL, '1', 'run', '10.00', NULL, '2026-10-03 21:09:16'),
('6', '1', NULL, '2', 'wicket', '1.00', NULL, '2026-10-03 21:09:16'),
('7', '1', NULL, '1', 'run', '10.00', NULL, '2026-10-03 21:09:16'),
('8', '1', NULL, '2', 'wicket', '1.00', NULL, '2026-10-03 21:09:16'),
('9', '1', NULL, '1', 'run', '6.00', NULL, '2026-10-03 21:27:57');

-- --------------------------------------------------
-- Table structure for `players`
-- --------------------------------------------------
DROP TABLE IF EXISTS `players`;
CREATE TABLE `players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'assets/images/default-player.png',
  `date_of_birth` date DEFAULT NULL,
  `jersey_number` int(11) DEFAULT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned DEFAULT NULL,
  `position` varchar(50) DEFAULT 'Player',
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_players_team` (`team_id`),
  KEY `idx_players_sport` (`sport_id`),
  CONSTRAINT `fk_players_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  CONSTRAINT `fk_players_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `players` (8 records)
INSERT INTO `players` (`id`, `name`, `email`, `phone`, `profile_image`, `date_of_birth`, `jersey_number`, `sport_id`, `team_id`, `position`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Rohit Sharma', 'player@sportshub.com', '+91 9800000001', 'assets/images/default-player.png', '1987-04-30', '45', '1', '1', 'Batsman', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('2', 'Jasprit Bumrah', 'jasprit@sportshub.com', '+91 9800000002', 'assets/images/default-player.png', '1993-12-06', '93', '1', '1', 'Bowler', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('3', 'Virat Kohli', 'virat@sportshub.com', '+91 9800000003', 'assets/images/default-player.png', '1988-11-05', '18', '1', '2', 'Batsman', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('4', 'Mohammed Siraj', 'siraj@sportshub.com', '+91 9800000004', 'assets/images/default-player.png', '1994-03-13', '13', '1', '2', 'Bowler', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('5', 'Sunil Chhetri', 'sunil@sportshub.com', '+91 9800000005', 'assets/images/default-player.png', '1984-08-03', '11', '2', '5', 'Forward', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('6', 'Gurpreet Singh', 'gurpreet@sportshub.com', '+91 9800000006', 'assets/images/default-player.png', '1992-02-03', '1', '2', '5', 'Goalkeeper', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('7', 'Pawan Sehrawat', 'pawan@sportshub.com', '+91 9800000007', 'assets/images/default-player.png', '1996-07-09', '7', '3', '7', 'Raider', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('8', 'Fazel Atrachali', 'fazel@sportshub.com', '+91 9800000008', 'assets/images/default-player.png', '1992-03-29', '1', '3', '7', 'Defender', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `points_table`
-- --------------------------------------------------
DROP TABLE IF EXISTS `points_table`;
CREATE TABLE `points_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tournament_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned NOT NULL,
  `played` int(11) DEFAULT 0,
  `won` int(11) DEFAULT 0,
  `lost` int(11) DEFAULT 0,
  `drawn` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `score_for` int(11) DEFAULT 0,
  `score_against` int(11) DEFAULT 0,
  `score_difference` int(11) DEFAULT 0,
  `net_run_rate` decimal(6,3) DEFAULT 0.000,
  `position` int(11) DEFAULT 1,
  `form` varchar(20) DEFAULT '',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tournament_team_points` (`tournament_id`,`team_id`),
  KEY `fk_pt_team` (`team_id`),
  CONSTRAINT `fk_pt_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `points_table` (10 records)
INSERT INTO `points_table` (`id`, `tournament_id`, `team_id`, `played`, `won`, `lost`, `drawn`, `points`, `score_for`, `score_against`, `score_difference`, `net_run_rate`, `position`, `form`, `updated_at`) VALUES
('1', '1', '3', '0', '0', '0', '0', '0', '0', '0', '0', '0.000', '3', '', '2026-10-03 21:28:04'),
('2', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0.000', '1', '', '2026-10-03 21:28:04'),
('3', '1', '2', '0', '0', '0', '0', '0', '0', '0', '0', '0.000', '2', '', '2026-10-03 21:28:04'),
('4', '1', '4', '0', '0', '0', '0', '0', '0', '0', '0', '0.000', '4', '', '2026-10-03 21:28:04'),
('5', '2', '5', '1', '1', '0', '0', '3', '2', '0', '2', '0.000', '1', 'W', '2026-10-03 20:46:22'),
('6', '2', '6', '1', '0', '1', '0', '0', '0', '2', '-2', '0.000', '2', 'L', '2026-10-03 20:46:22'),
('39', '3', '1', '1', '1', '0', '0', '2', '0', '0', '0', '0.000', '1', 'W', '2026-10-03 21:19:00'),
('40', '3', '7', '0', '0', '0', '0', '0', '0', '0', '0', '0.000', '1', '', '2026-10-03 21:32:59'),
('41', '3', '8', '0', '0', '0', '0', '0', '0', '0', '0', '0.000', '2', '', '2026-10-03 21:32:59'),
('42', '3', '2', '1', '0', '1', '0', '0', '0', '0', '0', '0.000', '4', 'L', '2026-10-03 21:19:00');

-- --------------------------------------------------
-- Table structure for `scores`
-- --------------------------------------------------
DROP TABLE IF EXISTS `scores`;
CREATE TABLE `scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned NOT NULL,
  `score` int(11) DEFAULT 0,
  `period` varchar(50) DEFAULT '1',
  `period_label` varchar(50) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match_team_score` (`match_id`,`team_id`),
  KEY `fk_scores_team` (`team_id`),
  CONSTRAINT `fk_scores_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scores_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `scores` (4 records)
INSERT INTO `scores` (`id`, `match_id`, `team_id`, `score`, `period`, `period_label`, `metadata`, `updated_at`) VALUES
('1', '1', '1', '174', '18.4 overs', 'Innings 2', 'Wickets: 4', '2026-10-03 20:46:22'),
('2', '1', '2', '185', '20.0 overs', 'Innings 1', 'Wickets: 6', '2026-10-03 20:46:22'),
('3', '3', '5', '2', '78th Min', '2nd Half', 'Goals: 2', '2026-10-03 20:46:22'),
('4', '3', '6', '1', '78th Min', '2nd Half', 'Goals: 1', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `settings` (14 records)
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('academic_year', '2025-2026', '2026-10-03 20:46:22'),
('championship_banner', 'assets/images/hero-bg.jpg', '2026-10-03 20:46:22'),
('championship_title', 'Inter-Department Sports Championship 2026', '2026-10-03 20:46:22'),
('college_logo', 'assets/images/college-logo.png', '2026-10-03 20:46:22'),
('college_name', 'Siddaganga Institute of Technology', '2026-10-03 20:46:22'),
('date_format', 'Y-m-d H:i:s', '2026-10-03 20:46:22'),
('default_timezone', 'Asia/Kolkata', '2026-10-03 20:46:22'),
('footer_text', '© Siddaganga Institute of Technology — Inter-Department Sports Championship', '2026-10-03 20:46:22'),
('notify_live', '1', '2026-10-03 20:46:22'),
('notify_results', '1', '2026-10-03 20:46:22'),
('notify_upcoming', '1', '2026-10-03 20:46:22'),
('primary_contact_email', 'sports@sit.ac.in', '2026-10-03 20:46:22'),
('primary_contact_phone', '+91 98765 43210', '2026-10-03 20:46:22'),
('public_visibility', '1', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `sports`
-- --------------------------------------------------
DROP TABLE IF EXISTS `sports`;
CREATE TABLE `sports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `sports` (8 records)
INSERT INTO `sports` (`id`, `name`, `slug`, `description`, `icon`, `status`, `created_at`) VALUES
('1', 'Cricket', 'cricket', 'Professional T20 and One Day Cricket Tournaments', 'cricket.svg', '1', '2026-10-03 20:46:22'),
('2', 'Football', 'football', '11-a-side and Futsal Football Championship', 'football.svg', '1', '2026-10-03 20:46:22'),
('3', 'Kabaddi', 'kabaddi', 'High-intensity Pro Kabaddi Indoor League', 'kabaddi.svg', '1', '2026-10-03 20:46:22'),
('4', 'Basketball', 'basketball', 'Standard 5v5 and 3v3 Hoops Arena League', 'basketball.svg', '1', '2026-10-03 20:46:22'),
('5', 'Volleyball', 'volleyball', '6v6 Indoor & Beach Volleyball Cup', 'volleyball.svg', '1', '2026-10-03 20:46:22'),
('6', 'Badminton', 'badminton', 'Singles and Doubles Shuttle Championship', 'badminton.svg', '1', '2026-10-03 20:46:22'),
('7', 'Tennis', 'tennis', 'Court Lawn and Hardcourt Tennis Open', 'tennis.svg', '1', '2026-10-03 20:46:22'),
('8', 'Table Tennis', 'table-tennis', 'Fast-paced Table Tennis Championship', 'table-tennis.svg', '1', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `team_statistics`
-- --------------------------------------------------
DROP TABLE IF EXISTS `team_statistics`;
CREATE TABLE `team_statistics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tournament_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned NOT NULL,
  `stat_type` varchar(50) NOT NULL,
  `stat_value` decimal(10,2) DEFAULT 0.00,
  `metadata` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tstats_tournament` (`tournament_id`),
  KEY `fk_tstats_team` (`team_id`),
  CONSTRAINT `fk_tstats_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tstats_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `teams`
-- --------------------------------------------------
DROP TABLE IF EXISTS `teams`;
CREATE TABLE `teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `short_name` varchar(10) NOT NULL,
  `logo` varchar(255) DEFAULT 'assets/images/default-team.png',
  `sport_id` int(10) unsigned NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `captain_id` int(10) unsigned DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_teams_sport` (`sport_id`),
  CONSTRAINT `fk_teams_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `teams` (8 records)
INSERT INTO `teams` (`id`, `name`, `short_name`, `logo`, `sport_id`, `department_id`, `captain_id`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Royal Strikers', 'RST', 'assets/images/default-team.png', '1', '1', '1', '5', '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('2', 'Thunder Warriors', 'TWR', 'assets/images/default-team.png', '1', '2', '3', NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('3', 'Rising Panthers', 'RPA', 'assets/images/default-team.png', '1', '3', NULL, NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('4', 'Coastal Kings', 'CKG', 'assets/images/default-team.png', '1', '3', NULL, NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('5', 'Apex Football Club', 'AFC', 'assets/images/default-team.png', '2', '1', '5', NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('6', 'City Titans FC', 'CTF', 'assets/images/default-team.png', '2', '2', NULL, NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('7', 'SSIT Bulls Kabaddi', 'SBK', 'assets/images/default-team.png', '3', '2', '7', NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32'),
('8', 'Delhi Raiders', 'DLR', 'assets/images/default-team.png', '3', '1', NULL, NULL, '1', '2026-10-03 20:46:22', '2026-10-03 20:54:32');

-- --------------------------------------------------
-- Table structure for `tournament_teams`
-- --------------------------------------------------
DROP TABLE IF EXISTS `tournament_teams`;
CREATE TABLE `tournament_teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tournament_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned NOT NULL,
  `group_name` varchar(10) DEFAULT 'A',
  `seed_number` int(10) unsigned DEFAULT NULL,
  `registration_status` enum('pending','approved','rejected') DEFAULT 'approved',
  `joined_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tournament_team` (`tournament_id`,`team_id`),
  KEY `fk_tt_team` (`team_id`),
  CONSTRAINT `fk_tt_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `tournament_teams` (8 records)
INSERT INTO `tournament_teams` (`id`, `tournament_id`, `team_id`, `group_name`, `seed_number`, `registration_status`, `joined_at`) VALUES
('1', '1', '1', 'Group A', NULL, 'approved', '2026-10-03 20:46:22'),
('2', '1', '2', 'Group A', NULL, 'approved', '2026-10-03 20:46:22'),
('3', '1', '3', 'Group B', NULL, 'approved', '2026-10-03 20:46:22'),
('4', '1', '4', 'Group B', NULL, 'approved', '2026-10-03 20:46:22'),
('5', '2', '5', 'League', NULL, 'approved', '2026-10-03 20:46:22'),
('6', '2', '6', 'League', NULL, 'approved', '2026-10-03 20:46:22'),
('7', '3', '7', 'Group A', NULL, 'approved', '2026-10-03 20:46:22'),
('8', '3', '8', 'Group A', NULL, 'approved', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `tournaments`
-- --------------------------------------------------
DROP TABLE IF EXISTS `tournaments`;
CREATE TABLE `tournaments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `logo` varchar(255) DEFAULT 'assets/images/default-tournament.png',
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `venue_id` int(10) unsigned DEFAULT NULL,
  `format` enum('league','knockout','round_robin','group_knockout') NOT NULL DEFAULT 'league',
  `status` enum('draft','upcoming','active','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_tournaments_venue` (`venue_id`),
  KEY `fk_tournaments_user` (`created_by`),
  KEY `idx_tournaments_sport` (`sport_id`),
  KEY `idx_tournaments_status` (`status`),
  CONSTRAINT `fk_tournaments_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  CONSTRAINT `fk_tournaments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tournaments_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `tournaments` (4 records)
INSERT INTO `tournaments` (`id`, `name`, `slug`, `sport_id`, `logo`, `description`, `start_date`, `end_date`, `venue_id`, `format`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'Champions Premier League 2026', 'champions-premier-league-2026', '1', 'assets/images/default-tournament.png', 'Premier T20 Cricket Championship featuring top clubs.', '2026-10-01', '2026-10-25', '1', 'group_knockout', 'active', '2', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('2', 'Super Football Cup 2026', 'super-football-cup-2026', '2', 'assets/images/default-tournament.png', 'National level 11-a-side Football League Tournament.', '2026-10-05', '2026-11-10', '2', 'league', 'active', '2', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('3', 'Inter College Kabaddi Championship', 'inter-college-kabaddi-championship', '3', 'assets/images/default-tournament.png', 'Inter-collegiate high-intensity indoor kabaddi championship.', '2026-10-10', '2026-10-30', '3', 'round_robin', 'upcoming', '2', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('4', 'SSIT Sports Fest 2026', 'ssit-sports-fest-2026', '4', 'assets/images/default-tournament.png', 'Annual Inter-departmental Multi-Sport Tournament.', '2026-10-15', '2026-10-28', '4', 'knockout', 'upcoming', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','organizer','scorer','official','team_manager','player') NOT NULL DEFAULT 'player',
  `profile_image` varchar(255) DEFAULT 'assets/images/default-avatar.png',
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `users` (6 records)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Alex Mercer', 'admin@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'admin', 'assets/images/default-avatar.png', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('2', 'Sarah Jenkins', 'organizer@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'organizer', 'assets/images/default-avatar.png', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('3', 'Nitin Menon', 'scorer@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'scorer', 'assets/images/default-avatar.png', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('4', 'Pranjal Banerjee', 'official@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'official', 'assets/images/default-avatar.png', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('5', 'Vikram Rathore', 'manager@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'team_manager', 'assets/images/default-avatar.png', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22'),
('6', 'Rohit Sharma', 'player@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'player', 'assets/images/default-avatar.png', '1', '2026-10-03 20:46:22', '2026-10-03 20:46:22');

-- --------------------------------------------------
-- Table structure for `venues`
-- --------------------------------------------------
DROP TABLE IF EXISTS `venues`;
CREATE TABLE `venues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `capacity` int(10) unsigned DEFAULT 0,
  `sport_type` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `venues` (5 records)
INSERT INTO `venues` (`id`, `name`, `location`, `address`, `city`, `capacity`, `sport_type`, `status`, `created_at`) VALUES
('1', 'Apex Sports Complex', 'Andheri Sports Enclave, Mumbai', 'Andheri Sports Enclave', 'Mumbai', '25000', 'Turf & Hardcourt', '1', '2026-10-03 20:46:22'),
('2', 'Grand National Arena', 'Kanteerava Stadium Road, Bengaluru', 'Kanteerava Stadium Road', 'Bengaluru', '35000', 'Natural Grass', '1', '2026-10-03 20:46:22'),
('3', 'Metro Indoor Stadium', 'Indira Gandhi Sports Complex, New Delhi', 'Indira Gandhi Sports Complex', 'New Delhi', '12000', 'Wooden Flooring', '1', '2026-10-03 20:46:22'),
('4', 'SSIT Sports Ground', 'SSIT Campus, Maralur, Tumakuru', 'SSIT Campus, Maralur', 'Tumakuru', '15000', 'Multi-purpose Turf', '1', '2026-10-03 20:46:22'),
('5', 'Coastal Turf Ground', 'Marina Beach Road, Chennai', 'Marina Beach Road', 'Chennai', '18000', 'Synthetic Grass', '1', '2026-10-03 20:46:22');

SET FOREIGN_KEY_CHECKS=1;
