-- ==========================================================
-- Database Schema for WDF Practicals (Practical 9 to 14)
-- Student ID: 25DCE075
-- Database Name: wdf_practicals_db
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `wdf_practicals_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wdf_practicals_db`;

-- ----------------------------------------------------------
-- 1. Users Table (Practicals 9, 10, 14)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `event_registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 2. Remember Me Tokens Table (Practical 10)
-- ----------------------------------------------------------
CREATE TABLE `remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 3. Students Table (Practicals 11, 13, 14)
-- ----------------------------------------------------------
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `enrollment_no` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `semester` INT NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `status` ENUM('active', 'deleted') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 4. Events Table (Practicals 12, 13, 14)
-- ----------------------------------------------------------
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `event_date` DATE NOT NULL,
    `venue` VARCHAR(100) NOT NULL,
    `poster_image` VARCHAR(255) NULL,
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 5. Event Registrations Table (Practical 14)
-- ----------------------------------------------------------
CREATE TABLE `event_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- 6. Audit Logs Table (Practicals 9, 12, 14)
-- ----------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================================
-- SEED DATA
-- Default Admin Password: AdminPassword123!
-- Default Student Password: StudentPassword123!
-- ==========================================================

-- Password hashes generated with PASSWORD_BCRYPT
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`) VALUES
(1, 'admin', 'admin@charusat.edu.in', '$2y$10$w6zKslhK6q.q3r/9Xl7Y4.9eI8Y5vTf.fA131F/iJ5rF.r67mI2gG', 'System Administrator', 'admin', 'active'),
(2, 'bhavya_patel', '25DCE075@charusat.edu.in', '$2y$10$w6zKslhK6q.q3r/9Xl7Y4.9eI8Y5vTf.fA131F/iJ5rF.r67mI2gG', 'Bhavya Patel', 'student', 'active'),
(3, 'rahul_shah', 'rahul@charusat.edu.in', '$2y$10$w6zKslhK6q.q3r/9Xl7Y4.9eI8Y5vTf.fA131F/iJ5rF.r67mI2gG', 'Rahul Shah', 'student', 'active');

-- Minimum 20 Seeded Students for Practical 11 requirement
INSERT INTO `students` (`enrollment_no`, `full_name`, `email`, `mobile`, `course`, `semester`, `gender`, `status`) VALUES
('25DCE001', 'Aarav Sharma', 'aarav.sharma@example.com', '9825011111', 'Computer Engineering', 4, 'Male', 'active'),
('25DCE002', 'Aditi Verma', 'aditi.verma@example.com', '9825011112', 'Computer Engineering', 4, 'Female', 'active'),
('25DCE003', 'Ananya Joshi', 'ananya.joshi@example.com', '9825011113', 'Information Technology', 4, 'Female', 'active'),
('25DCE004', 'Aryan Dave', 'aryan.dave@example.com', '9825011114', 'Computer Engineering', 4, 'Male', 'active'),
('25DCE005', 'Bhavya Dave', 'bhavya.dave@example.com', '9825011115', 'Information Technology', 6, 'Male', 'active'),
('25DCE006', 'Charmi Shah', 'charmi.shah@example.com', '9825011116', 'Computer Science', 2, 'Female', 'active'),
('25DCE007', 'Devansh Mehta', 'devansh.mehta@example.com', '9825011117', 'Computer Engineering', 4, 'Male', 'active'),
('25DCE008', 'Diya Trivedi', 'diya.trivedi@example.com', '9825011118', 'Information Technology', 4, 'Female', 'active'),
('25DCE009', 'Hardik Pandya', 'hardik.pandya@example.com', '9825011119', 'Computer Engineering', 6, 'Male', 'active'),
('25DCE010', 'Ishaan Vora', 'ishaan.vora@example.com', '9825011120', 'Computer Science', 2, 'Male', 'active'),
('25DCE011', 'Jiya Solanki', 'jiya.solanki@example.com', '9825011121', 'Computer Engineering', 4, 'Female', 'active'),
('25DCE012', 'Karan Parmar', 'karan.parmar@example.com', '9825011122', 'Information Technology', 6, 'Male', 'active'),
('25DCE013', 'Khushi Prajapati', 'khushi.p@example.com', '9825011123', 'Computer Engineering', 2, 'Female', 'active'),
('25DCE014', 'Manan Bhatt', 'manan.bhatt@example.com', '9825011124', 'Computer Science', 4, 'Male', 'active'),
('25DCE015', 'Nandini Rao', 'nandini.rao@example.com', '9825011125', 'Computer Engineering', 4, 'Female', 'active'),
('25DCE016', 'Omkar Desai', 'omkar.desai@example.com', '9825011126', 'Information Technology', 6, 'Male', 'active'),
('25DCE017', 'Pooja Barot', 'pooja.barot@example.com', '9825011127', 'Computer Engineering', 4, 'Female', 'active'),
('25DCE018', 'Pranav Kothari', 'pranav.kothari@example.com', '9825011128', 'Computer Science', 6, 'Male', 'active'),
('25DCE019', 'Riya Makwana', 'riya.makwana@example.com', '9825011129', 'Information Technology', 2, 'Female', 'active'),
('25DCE020', 'Rohan Soni', 'rohan.soni@example.com', '9825011130', 'Computer Engineering', 4, 'Male', 'active'),
('25DCE075', 'Bhavya Patel', '25DCE075@charusat.edu.in', '9825099926', 'Computer Engineering', 4, 'Male', 'active');

-- Sample Events for Practical 12
INSERT INTO `events` (`title`, `category`, `description`, `event_date`, `venue`, `poster_image`, `status`) VALUES
('Spoural Annual Tech Fest 2026', 'Technical', 'State-level mega technical competition including hackathons and web designing challenges.', '2026-11-15', 'Central Auditorium', 'default_event.png', 'open'),
('AI & Cloud Computing Workshop', 'Workshop', 'Hands-on bootcamp on modern cloud deployment and generative AI models.', '2026-11-20', 'Lab 301, Computer Dept', 'default_event.png', 'open'),
('Cyber Security & Ethical Hacking Symposium', 'Seminar', 'Expert session on securing web applications, OWASP Top 10, and pen-testing.', '2026-12-05', 'Seminar Hall 2', 'default_event.png', 'open'),
('Code-A-Thon 24 Hour Coding Sprint', 'Competition', 'Intense competitive programming event for all computer and IT students.', '2026-10-30', 'CS Department Labs', 'default_event.png', 'closed');

-- Sample Event Registrations for Practical 14
INSERT INTO `event_registrations` (`event_id`, `student_id`) VALUES
(1, 1), (1, 2), (1, 4), (1, 21),
(2, 3), (2, 7), (2, 21),
(3, 8), (3, 10), (3, 21);

-- Initial Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `ip_address`, `user_agent`) VALUES
(1, 'SYSTEM_INIT', 'Database seeded with default credentials and sample dataset.', '127.0.0.1', 'SeedScript/1.0');
