-- ============================================================
-- Admin Panel & Contact Form Additions
-- Run this AFTER importing the main study_planner SQL file
-- ============================================================

-- Add is_admin column to users table (1 = admin, 0 = student)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `is_admin` TINYINT(1) NOT NULL DEFAULT 0;

-- Add status column to users (active / banned)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `status` ENUM('active','banned') NOT NULL DEFAULT 'active';

-- Create contact_messages table
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` ENUM('unread','read','replied') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create site_settings table
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default settings
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Study Planner'),
('contact_email', 'admin@studyplanner.com'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_from_name', 'Study Planner'),
('about_heading', 'About Study Planner'),
('about_text', 'Study Planner is a comprehensive platform designed to help students organize their academic life, track progress, and achieve their goals.');

-- Set user id=1 as admin (first user is admin by default)
UPDATE `users` SET `is_admin` = 1 WHERE `id` = 1;

COMMIT;
