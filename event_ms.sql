-- ============================================================
--  Event Management System — Database Schema
--  Compatible with: MySQL 5.7+ / MariaDB 10.4+
-- ============================================================

CREATE DATABASE IF NOT EXISTS event_ms;
USE ems;

-- ─── USERS ──────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(60)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('organizer','participant') NOT NULL DEFAULT 'participant',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── EVENTS ─────────────────────────────────────────────────
CREATE TABLE `events` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `organizer_id` INT(11)     NOT NULL,
  `title`       VARCHAR(160) NOT NULL,
  `type`        ENUM('seminar','workshop','meeting','conference','webinar','other') NOT NULL DEFAULT 'seminar',
  `description` TEXT         DEFAULT NULL,
  `location`    VARCHAR(255) DEFAULT NULL,
  `event_date`  DATE         NOT NULL,
  `event_time`  TIME         NOT NULL,
  `capacity`    INT(6)       NOT NULL DEFAULT 50,
  `status`      ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_events_organizer` (`organizer_id`),
  CONSTRAINT `fk_events_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── REGISTRATIONS ──────────────────────────────────────────
CREATE TABLE `registrations` (
  `id`              INT(11)   NOT NULL AUTO_INCREMENT,
  `event_id`        INT(11)   NOT NULL,
  `participant_id`  INT(11)   NOT NULL,
  `registered_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`          ENUM('registered','cancelled') NOT NULL DEFAULT 'registered',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg` (`event_id`,`participant_id`),
  KEY `fk_reg_event` (`event_id`),
  KEY `fk_reg_participant` (`participant_id`),
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_participant` FOREIGN KEY (`participant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── SEED DATA ───────────────────────────────────────────────
-- Password for both accounts: Password123
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('usrat',   'usrat@demo.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer'),
('fariya',    'fariya@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'participant');

INSERT INTO `events` (`organizer_id`,`title`,`type`,`description`,`location`,`event_date`,`event_time`,`capacity`,`status`) VALUES
(1,'Laravel Deep Dive Workshop','workshop','An immersive hands-on session covering Laravel 11 features, Eloquent, and modern PHP practices.','Room 301 – Tech Hub',DATE_ADD(CURDATE(), INTERVAL 7 DAY),'09:00:00',30,'upcoming'),
(1,'AI in Business Seminar','seminar','Explore how AI is reshaping industries, with case studies from Fortune 500 companies.','Main Auditorium',DATE_ADD(CURDATE(), INTERVAL 14 DAY),'14:00:00',100,'upcoming'),
(1,'Q2 Strategy Meeting','meeting','Internal quarterly planning session for the product and engineering teams.','Conference Room A',DATE_ADD(CURDATE(), INTERVAL 3 DAY),'10:30:00',20,'upcoming');
