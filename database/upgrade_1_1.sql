-- Upgrade a version 1.0 database to 1.1 (fixed slots + teacher-less subjects).
USE `timetable`;
ALTER TABLE `subjects` ADD COLUMN `needs_teacher` tinyint(1) NOT NULL DEFAULT 1 AFTER `difficulty`;
CREATE TABLE IF NOT EXISTS `fixed_slots` (
  `fixed_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL DEFAULT 0,
  `subject_id` int(11) NOT NULL,
  `day_no` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  PRIMARY KEY (`fixed_id`),
  UNIQUE KEY `slot` (`class_id`, `subject_id`, `day_no`, `period_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `rules` (`rule_key`, `rule_label`, `kind`, `weight`, `sort_no`) VALUES
('fixed_slots', 'Fixed slots (e.g. Saturday revision) are always honoured and never moved', 'hard', 0, 8);
