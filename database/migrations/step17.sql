-- Step 17: User, Role, Permission & Security Management Migration

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `user_name` VARCHAR(255) NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity` VARCHAR(100) NULL,
    `entity_id` INT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('college_name', 'Siddaganga Institute of Technology'),
('championship_title', 'Inter-Department Sports Championship 2026'),
('academic_year', '2025-2026'),
('college_logo', 'assets/images/college-logo.png'),
('championship_banner', 'assets/images/hero-bg.jpg'),
('primary_contact_email', 'sports@sit.ac.in'),
('primary_contact_phone', '+91 98765 43210'),
('default_timezone', 'Asia/Kolkata'),
('date_format', 'Y-m-d H:i:s'),
('footer_text', '© Siddaganga Institute of Technology — Inter-Department Sports Championship'),
('public_visibility', '1'),
('notify_upcoming', '1'),
('notify_live', '1'),
('notify_results', '1');
