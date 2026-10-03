-- Step 22 Notification & Announcement System Migration

ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `link_url` VARCHAR(255) NULL AFTER `type`;
ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `priority` VARCHAR(20) DEFAULT 'Normal' AFTER `link_url`;

ALTER TABLE `announcements` ADD COLUMN IF NOT EXISTS `target_audience` VARCHAR(50) DEFAULT 'Everyone' AFTER `sport`;
ALTER TABLE `announcements` ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) NULL AFTER `target_audience`;
ALTER TABLE `announcements` ADD COLUMN IF NOT EXISTS `publish_date` DATETIME NULL AFTER `status`;
