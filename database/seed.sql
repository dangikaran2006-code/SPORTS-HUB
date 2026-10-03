-- =============================================================================
-- SportsHub - Multi-Sport Tournament Management Platform
-- Seed Data Set for `sportshub` Database (With Test Accounts for All Roles)
-- =============================================================================

USE `sportshub`;

-- 1. SEED USERS (Test Accounts for All 6 Roles)
-- Default Password for all test users: Password123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Alex Mercer', 'admin@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'admin', 1),
(2, 'Sarah Jenkins', 'organizer@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'organizer', 1),
(3, 'Nitin Menon', 'scorer@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'scorer', 1),
(4, 'Pranjal Banerjee', 'official@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'official', 1),
(5, 'Vikram Rathore', 'manager@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'team_manager', 1),
(6, 'Rohit Sharma', 'player@sportshub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1N./w7K8x1K1U7O7yB7N0Q6L8bC9K6G', 'player', 1);

-- 2. SEED 8 SPORTS
INSERT INTO `sports` (`id`, `name`, `slug`, `description`, `icon`, `status`) VALUES
(1, 'Cricket', 'cricket', 'Professional T20 and One Day Cricket Tournaments', 'cricket.svg', 1),
(2, 'Football', 'football', '11-a-side and Futsal Football Championship', 'football.svg', 1),
(3, 'Kabaddi', 'kabaddi', 'High-intensity Pro Kabaddi Indoor League', 'kabaddi.svg', 1),
(4, 'Basketball', 'basketball', 'Standard 5v5 and 3v3 Hoops Arena League', 'basketball.svg', 1),
(5, 'Volleyball', 'volleyball', '6v6 Indoor & Beach Volleyball Cup', 'volleyball.svg', 1),
(6, 'Badminton', 'badminton', 'Singles and Doubles Shuttle Championship', 'badminton.svg', 1),
(7, 'Tennis', 'tennis', 'Court Lawn and Hardcourt Tennis Open', 'tennis.svg', 1),
(8, 'Table Tennis', 'table-tennis', 'Fast-paced Table Tennis Championship', 'table-tennis.svg', 1);

-- 3. SEED 5 VENUES
INSERT INTO `venues` (`id`, `name`, `address`, `city`, `capacity`, `sport_type`, `status`) VALUES
(1, 'Apex Sports Complex', 'Andheri Sports Enclave', 'Mumbai', 25000, 'Turf & Hardcourt', 1),
(2, 'Grand National Arena', 'Kanteerava Stadium Road', 'Bengaluru', 35000, 'Natural Grass', 1),
(3, 'Metro Indoor Stadium', 'Indira Gandhi Sports Complex', 'New Delhi', 12000, 'Wooden Flooring', 1),
(4, 'SSIT Sports Ground', 'SSIT Campus, Maralur', 'Tumakuru', 15000, 'Multi-purpose Turf', 1),
(5, 'Coastal Turf Ground', 'Marina Beach Road', 'Chennai', 18000, 'Synthetic Grass', 1);

-- 4. SEED TOURNAMENTS
INSERT INTO `tournaments` (`id`, `name`, `slug`, `sport_id`, `description`, `start_date`, `end_date`, `venue_id`, `format`, `status`, `created_by`) VALUES
(1, 'Champions Premier League 2026', 'champions-premier-league-2026', 1, 'Premier T20 Cricket Championship featuring top clubs.', '2026-10-01', '2026-10-25', 1, 'group_knockout', 'active', 2),
(2, 'Super Football Cup 2026', 'super-football-cup-2026', 2, 'National level 11-a-side Football League Tournament.', '2026-10-05', '2026-11-10', 2, 'league', 'active', 2),
(3, 'Inter College Kabaddi Championship', 'inter-college-kabaddi-championship', 3, 'Inter-collegiate high-intensity indoor kabaddi championship.', '2026-10-10', '2026-10-30', 3, 'round_robin', 'upcoming', 2),
(4, 'SSIT Sports Fest 2026', 'ssit-sports-fest-2026', 4, 'Annual Inter-departmental Multi-Sport Tournament.', '2026-10-15', '2026-10-28', 4, 'knockout', 'upcoming', 1);

-- 5. SEED TEAMS
INSERT INTO `teams` (`id`, `name`, `short_name`, `sport_id`, `manager_id`, `status`) VALUES
(1, 'Royal Strikers', 'RST', 1, 5, 1),
(2, 'Thunder Warriors', 'TWR', 1, NULL, 1),
(3, 'Rising Panthers', 'RPA', 1, NULL, 1),
(4, 'Coastal Kings', 'CKG', 1, NULL, 1),
(5, 'Apex Football Club', 'AFC', 2, NULL, 1),
(6, 'City Titans FC', 'CTF', 2, NULL, 1),
(7, 'SSIT Bulls Kabaddi', 'SBK', 3, NULL, 1),
(8, 'Delhi Raiders', 'DLR', 3, NULL, 1);

-- 6. MAP TOURNAMENT TEAMS
INSERT INTO `tournament_teams` (`tournament_id`, `team_id`, `group_name`, `registration_status`) VALUES
(1, 1, 'Group A', 'approved'),
(1, 2, 'Group A', 'approved'),
(1, 3, 'Group B', 'approved'),
(1, 4, 'Group B', 'approved'),
(2, 5, 'League', 'approved'),
(2, 6, 'League', 'approved'),
(3, 7, 'Group A', 'approved'),
(3, 8, 'Group A', 'approved');

-- 7. SEED PLAYERS
INSERT INTO `players` (`id`, `name`, `email`, `phone`, `date_of_birth`, `jersey_number`, `sport_id`, `team_id`, `position`, `status`) VALUES
(1, 'Rohit Sharma', 'player@sportshub.com', '+91 9800000001', '1987-04-30', 45, 1, 1, 'Batsman', 1),
(2, 'Jasprit Bumrah', 'jasprit@sportshub.com', '+91 9800000002', '1993-12-06', 93, 1, 1, 'Bowler', 1),
(3, 'Virat Kohli', 'virat@sportshub.com', '+91 9800000003', '1988-11-05', 18, 1, 2, 'Batsman', 1),
(4, 'Mohammed Siraj', 'siraj@sportshub.com', '+91 9800000004', '1994-03-13', 13, 1, 2, 'Bowler', 1),
(5, 'Sunil Chhetri', 'sunil@sportshub.com', '+91 9800000005', '1984-08-03', 11, 2, 5, 'Forward', 1),
(6, 'Gurpreet Singh', 'gurpreet@sportshub.com', '+91 9800000006', '1992-02-03', 1, 2, 5, 'Goalkeeper', 1),
(7, 'Pawan Sehrawat', 'pawan@sportshub.com', '+91 9800000007', '1996-07-09', 7, 3, 7, 'Raider', 1),
(8, 'Fazel Atrachali', 'fazel@sportshub.com', '+91 9800000008', '1992-03-29', 1, 3, 7, 'Defender', 1);

-- Update captain references
UPDATE `teams` SET `captain_id` = 1 WHERE `id` = 1;
UPDATE `teams` SET `captain_id` = 3 WHERE `id` = 2;
UPDATE `teams` SET `captain_id` = 5 WHERE `id` = 5;
UPDATE `teams` SET `captain_id` = 7 WHERE `id` = 7;

-- 8. SEED OFFICIALS
INSERT INTO `officials` (`id`, `user_id`, `name`, `role`, `email`, `sport_id`, `status`) VALUES
(1, 3, 'Nitin Menon', 'scorer', 'scorer@sportshub.com', 1, 1),
(2, 4, 'Pranjal Banerjee', 'referee', 'official@sportshub.com', 2, 1),
(3, NULL, 'Kumar Dharmasena', 'umpire', 'kumar.d@referee.org', 1, 1);

-- 9. SEED MATCHES
INSERT INTO `matches` (`id`, `tournament_id`, `sport_id`, `team_a_id`, `team_b_id`, `venue_id`, `official_id`, `scheduled_date`, `scheduled_time`, `status`, `result_summary`) VALUES
(1, 1, 1, 1, 2, 1, 1, '2026-10-03', '19:30:00', 'live', 'Royal Strikers need 12 runs off 8 balls'),
(2, 1, 1, 3, 4, 1, 3, '2026-10-02', '16:00:00', 'completed', 'Rising Panthers won by 4 wickets'),
(3, 2, 2, 5, 6, 2, 2, '2026-10-03', '20:00:00', 'live', 'Apex FC leads 2 - 1'),
(4, 3, 3, 7, 8, 3, 1, '2026-10-10', '18:00:00', 'scheduled', 'Scheduled kickoff at 6:00 PM');

-- 10. SEED SCORES
INSERT INTO `scores` (`id`, `match_id`, `team_id`, `score`, `period`, `period_label`, `metadata`) VALUES
(1, 1, 1, 174, '18.4 overs', 'Innings 2', 'Wickets: 4'),
(2, 1, 2, 185, '20.0 overs', 'Innings 1', 'Wickets: 6'),
(3, 3, 5, 2, '78th Min', '2nd Half', 'Goals: 2'),
(4, 3, 6, 1, '78th Min', '2nd Half', 'Goals: 1');

-- 11. SEED MATCH EVENTS
INSERT INTO `match_events` (`id`, `match_id`, `team_id`, `player_id`, `event_type`, `event_value`, `event_time`, `event_data`) VALUES
(1, 1, 1, 1, 'run', 6, '17.2 overs', 'Rohit Sharma hits 6 over deep mid-wicket!'),
(2, 3, 5, 5, 'goal', 1, '24\'', 'Sunil Chhetri headers into top corner!');

-- 12. SEED POINTS TABLE
INSERT INTO `points_table` (`tournament_id`, `team_id`, `played`, `won`, `lost`, `drawn`, `points`, `score_for`, `score_against`, `score_difference`, `net_run_rate`, `position`, `form`) VALUES
(1, 3, 1, 1, 0, 0, 2, 162, 158, 4, 0.450, 1, 'W'),
(1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0.000, 2, '-'),
(1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0.000, 3, '-'),
(1, 4, 1, 0, 1, 0, 0, 158, 162, -4, -0.450, 4, 'L'),
(2, 5, 1, 1, 0, 0, 3, 2, 0, 2, 0.000, 1, 'W'),
(2, 6, 1, 0, 1, 0, 0, 0, 2, -2, 0.000, 2, 'L');

-- 13. AUDIT LOGS SEED
INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`) VALUES
(1, 'DATABASE_INIT', 'SYSTEM', 1, 'Initial Database Schema & Seed Data Loaded Successfully', '127.0.0.1');
