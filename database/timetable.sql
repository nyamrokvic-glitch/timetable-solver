SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `timetable`
--
CREATE DATABASE IF NOT EXISTS `timetable` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `timetable`;

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- default login: admin / admin  (change it in Settings)
INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`) VALUES
(1, 'admin', '$2y$10$8JNMf8iMQYyH9JRexNneQOFJaf7XXDzO0Xqb5TyVFXDXd/8axTl86', '', 'admin');

-- --------------------------------------------------------
-- settings (school name, teaching days, baseline hours ...)
-- --------------------------------------------------------
CREATE TABLE `settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'My School'),
('days', 'Mon,Tue,Wed,Thu,Fri'),
('manual_hours', '40');

-- --------------------------------------------------------
-- periods : the lesson slots of one school day.
-- break_after = 1 means a break/lunch follows this period, so a
-- double lesson can never start on it.
-- --------------------------------------------------------
CREATE TABLE `periods` (
  `period_no` int(11) NOT NULL,
  `start_time` varchar(5) NOT NULL,
  `end_time` varchar(5) NOT NULL,
  `break_after` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`period_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `periods` (`period_no`, `start_time`, `end_time`, `break_after`) VALUES
(1, '08:00', '08:40', 0),
(2, '08:40', '09:20', 0),
(3, '09:20', '10:00', 1),
(4, '10:30', '11:10', 0),
(5, '11:10', '11:50', 0),
(6, '11:50', '12:30', 1),
(7, '13:30', '14:10', 0),
(8, '14:10', '14:50', 0);

-- --------------------------------------------------------
-- teachers
-- --------------------------------------------------------
CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_name` varchar(255) NOT NULL,
  `teacher_code` varchar(10) NOT NULL DEFAULT '',
  `max_per_day` int(11) NOT NULL DEFAULT 6,
  `max_per_week` int(11) NOT NULL DEFAULT 28,
  `part_time` tinyint(1) NOT NULL DEFAULT 0,
  `teacher_active` int(11) NOT NULL DEFAULT 1,
  `teacher_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- subjects a teacher is qualified to teach
CREATE TABLE `teacher_subjects` (
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  PRIMARY KEY (`teacher_id`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- per-slot availability. kind: 'unavailable' (hard) | 'avoid' (soft)
CREATE TABLE `teacher_slots` (
  `teacher_id` int(11) NOT NULL,
  `day_no` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  `kind` varchar(12) NOT NULL,
  PRIMARY KEY (`teacher_id`, `day_no`, `period_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- subjects
--   room_type  : '' = ordinary classroom, otherwise a room type (e.g. chem_lab)
--   room_scope : 'all' = every lesson needs that room, 'doubles' = only double lessons
--   difficulty : 1 light, 2 medium, 3 demanding
--   needs_teacher : 0 for supervised sessions such as Revision (no teacher is scheduled)
-- --------------------------------------------------------
CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(255) NOT NULL,
  `subject_code` varchar(10) NOT NULL DEFAULT '',
  `room_type` varchar(40) NOT NULL DEFAULT '',
  `room_scope` varchar(10) NOT NULL DEFAULT 'all',
  `difficulty` int(11) NOT NULL DEFAULT 2,
  `needs_teacher` tinyint(1) NOT NULL DEFAULT 1,
  `subject_active` int(11) NOT NULL DEFAULT 1,
  `subject_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- classes
-- --------------------------------------------------------
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(255) NOT NULL,
  `level` varchar(40) NOT NULL DEFAULT '',
  `students` int(11) NOT NULL DEFAULT 40,
  `class_active` int(11) NOT NULL DEFAULT 1,
  `class_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- rooms (special rooms: labs, computer rooms, fields, halls ...)
-- --------------------------------------------------------
CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(255) NOT NULL,
  `room_type` varchar(40) NOT NULL DEFAULT '',
  `capacity` int(11) NOT NULL DEFAULT 40,
  `room_active` int(11) NOT NULL DEFAULT 1,
  `room_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- allocations : "class X is taught subject Y by teacher Z, N periods a week,
-- of which `doubles` blocks are double lessons"
-- --------------------------------------------------------
CREATE TABLE `allocations` (
  `alloc_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `periods_per_week` int(11) NOT NULL DEFAULT 3,
  `doubles` int(11) NOT NULL DEFAULT 0,
  `alloc_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`alloc_id`),
  KEY `class_id` (`class_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- fixed_slots : lessons the school never moves, e.g. Saturday-afternoon
-- Revision. class_id 0 = every class that has the subject.
-- --------------------------------------------------------
CREATE TABLE `fixed_slots` (
  `fixed_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL DEFAULT 0,
  `subject_id` int(11) NOT NULL,
  `day_no` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  PRIMARY KEY (`fixed_id`),
  UNIQUE KEY `slot` (`class_id`, `subject_id`, `day_no`, `period_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- rules : hard rules are listed for transparency, soft rules carry a weight
-- (0 = switched off). The weights are the "how much does the school care" dial.
-- --------------------------------------------------------
CREATE TABLE `rules` (
  `rule_key` varchar(40) NOT NULL,
  `rule_label` varchar(255) NOT NULL,
  `kind` varchar(8) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `sort_no` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `rules` (`rule_key`, `rule_label`, `kind`, `weight`, `sort_no`) VALUES
('teacher_clash',    'A teacher can only be in one place at a time', 'hard', 0, 1),
('class_clash',      'A class can only have one lesson at a time', 'hard', 0, 2),
('room_clash',       'A lab / special room hosts one class at a time', 'hard', 0, 3),
('teacher_blocked',  'Teachers are never placed in slots marked Unavailable', 'hard', 0, 4),
('room_fit',         'Lab subjects only go to a matching room with enough capacity', 'hard', 0, 5),
('teacher_load',     'Respect every teacher''s maximum lessons per day and per week', 'hard', 0, 6),
('break_double',     'Double lessons never straddle a break or lunch', 'hard', 0, 7),
('fixed_slots',      'Fixed slots (e.g. Saturday revision) are always honoured and never moved', 'hard', 0, 8),
('learned_subject_period', 'Put each subject at the times of day this school has used before (learned)', 'soft', 3, 10),
('learned_subject_day',    'Put each subject on the days of the week this school has used before (learned)', 'soft', 1, 11),
('learned_double_start',   'Start double lessons where this school normally starts them (learned)', 'soft', 2, 12),
('learned_teacher_period', 'Follow each teacher''s habitual time-of-day pattern (learned)', 'soft', 1, 13),
('teacher_avoid',    'Honour slots a teacher prefers to avoid', 'soft', 6, 20),
('teacher_gaps',     'Avoid idle periods between a teacher''s lessons', 'soft', 3, 21),
('long_gap',         'Extra penalty for teacher gaps longer than this school normally tolerates', 'soft', 3, 22),
('teacher_balance',  'Spread a teacher''s lessons evenly over their days', 'soft', 1, 23),
('subject_once_per_day', 'A class does not get the same subject twice in a day (doubles excepted)', 'soft', 10, 30),
('class_gaps',       'A class''s free periods fall at the end of the day, not in the middle', 'soft', 4, 31),
('heavy_back_to_back', 'Avoid back-to-back demanding subjects for a class', 'soft', 2, 32),
('heavy_late',       'Keep demanding subjects out of the last two periods', 'soft', 1, 33);

-- --------------------------------------------------------
-- timetables and their lessons
--   status: draft | accepted | historical | archived
--   accepted + historical timetables are what the solver learns from
-- --------------------------------------------------------
CREATE TABLE `timetables` (
  `timetable_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `term` varchar(60) NOT NULL DEFAULT '',
  `source` varchar(12) NOT NULL DEFAULT 'generated',
  `status` varchar(12) NOT NULL DEFAULT 'draft',
  `score` double NOT NULL DEFAULT 0,
  `habit_match` double NOT NULL DEFAULT 0,
  `unplaced` int(11) NOT NULL DEFAULT 0,
  `run_seconds` double NOT NULL DEFAULT 0,
  `notes` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`timetable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `timetable_lessons` (
  `lesson_id` int(11) NOT NULL AUTO_INCREMENT,
  `timetable_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `day_no` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  PRIMARY KEY (`lesson_id`),
  KEY `timetable_id` (`timetable_id`),
  KEY `slot` (`timetable_id`, `day_no`, `period_no`),
  CONSTRAINT `fk_lesson_tt` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`timetable_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
