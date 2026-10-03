-- Step 18: Schema Repair Migration - Departments, Announcements & Teams Association

CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `short_code` VARCHAR(20) NOT NULL,
    `color_code` VARCHAR(20) DEFAULT '#00e676',
    `logo` VARCHAR(255) DEFAULT 'assets/images/dept-cse.png',
    `contact_person` VARCHAR(150) DEFAULT '',
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `departments` (`id`, `name`, `short_code`, `color_code`, `logo`, `contact_person`, `status`) VALUES
(1, 'Computer Engineering / BCA', 'CSE', '#00e676', 'assets/images/dept-cse.png', 'Dr. Alan Turing', 1),
(2, 'Mechanical Engineering', 'ME', '#3b82f6', 'assets/images/dept-me.png', 'Prof. Nikola Tesla', 1),
(3, 'Civil Engineering', 'CE', '#f59e0b', 'assets/images/dept-ce.png', 'Er. Isambard Brunel', 1),
(4, 'Electrical & Electronics', 'EEE', '#ec4899', 'assets/images/dept-eee.png', 'Dr. Michael Faraday', 1),
(5, 'Information Technology', 'IT', '#8b5cf6', 'assets/images/dept-it.png', 'Prof. Tim Berners-Lee', 1),
(6, 'Electronics & Communication', 'ECE', '#06b6d4', 'assets/images/dept-ece.png', 'Dr. Guglielmo Marconi', 1);

CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `sport` VARCHAR(100) DEFAULT 'General',
    `priority` ENUM('Normal', 'Important', 'Urgent') DEFAULT 'Normal',
    `status` VARCHAR(50) DEFAULT 'Published',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `announcements` (`id`, `title`, `message`, `sport`, `priority`, `status`) VALUES
(1, '🏏 Cricket Championship Final Rescheduled Time', 'The Grand Cricket Final between Computer Engineering (CSE) and Mechanical Engineering (ME) will commence at 4:00 PM IST today at the Main Ground.', 'Cricket', 'Urgent', 'Published'),
(2, '🏸 Badminton Singles Venue Allocation Update', 'Due to rain forecast, all Badminton Men Singles & Mixed Doubles matches have been shifted to Indoor Sports Complex Court 1 & Court 2.', 'Badminton', 'Important', 'Published'),
(3, '🏆 Overall Department Points Calculation Rules Published', 'The Sports Board has published the official Point Matrix for AY 2025-2026. Team Sports Gold: 10pts, Silver: 7pts, Bronze: 5pts.', 'General', 'Normal', 'Published');

ALTER TABLE `teams` ADD COLUMN IF NOT EXISTS `department_id` INT NULL AFTER `sport_id`;
