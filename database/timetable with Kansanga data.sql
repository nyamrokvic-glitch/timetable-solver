
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `timetable` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `timetable`;
DROP TABLE IF EXISTS `allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `allocations` WRITE;
/*!40000 ALTER TABLE `allocations` DISABLE KEYS */;
INSERT INTO `allocations` VALUES
(1,1,6,9,3,0,1),
(2,1,3,4,2,0,1),
(3,1,5,7,3,0,1),
(4,1,1,1,4,0,1),
(5,1,4,5,6,0,1),
(6,1,8,12,4,0,1),
(7,2,6,10,3,0,1),
(8,2,1,1,6,0,1),
(9,2,8,13,3,0,1),
(10,2,3,4,3,0,1),
(11,2,11,16,3,0,1),
(12,3,1,2,5,0,1),
(13,3,5,8,4,0,1),
(14,3,6,9,3,0,1),
(15,3,11,16,4,1,1),
(16,3,4,6,5,0,1),
(17,4,5,7,3,0,1),
(18,4,11,17,4,0,1),
(19,4,1,1,6,0,1),
(20,4,10,15,2,0,1),
(21,4,8,13,3,0,1),
(22,4,2,3,3,0,1),
(23,1,13,19,3,1,1),
(24,1,7,11,2,0,1),
(25,1,12,18,2,0,1),
(26,2,10,15,3,0,1),
(27,2,13,20,3,0,1),
(28,3,12,18,2,0,1),
(29,3,13,19,2,0,1),
(30,3,9,14,2,0,1),
(31,4,9,14,2,0,1),
(32,4,13,19,2,0,1),
(33,4,4,5,3,0,1),
(34,1,2,3,1,0,1),
(35,2,4,5,4,0,1),
(36,2,9,14,1,0,1),
(37,3,3,4,3,0,1),
(38,3,2,3,1,0,1),
(39,3,7,11,1,0,1),
(40,4,6,10,3,0,1),
(41,4,3,4,2,0,1),
(42,1,10,15,2,0,1),
(43,2,2,3,2,0,1),
(44,2,5,7,2,0,1),
(45,3,8,12,1,0,1),
(46,4,7,11,1,0,1),
(47,1,9,14,1,0,1),
(48,1,11,16,1,0,1),
(49,2,12,18,1,0,1),
(50,3,10,15,1,0,1),
(51,1,14,0,2,1,1),
(52,2,14,0,2,1,1),
(53,3,14,0,2,1,1),
(54,4,14,0,2,1,1);
/*!40000 ALTER TABLE `allocations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(255) NOT NULL,
  `level` varchar(40) NOT NULL DEFAULT '',
  `students` int(11) NOT NULL DEFAULT 40,
  `class_active` int(11) NOT NULL DEFAULT 1,
  `class_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES
(1,'S.1','S.1',40,1,1),
(2,'S.2','S.2',40,1,1),
(3,'S.3','S.3',40,1,1),
(4,'S.4','S.4',40,1,1);
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fixed_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixed_slots` (
  `fixed_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL DEFAULT 0,
  `subject_id` int(11) NOT NULL,
  `day_no` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  PRIMARY KEY (`fixed_id`),
  UNIQUE KEY `slot` (`class_id`,`subject_id`,`day_no`,`period_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fixed_slots` WRITE;
/*!40000 ALTER TABLE `fixed_slots` DISABLE KEYS */;
INSERT INTO `fixed_slots` VALUES
(1,0,14,6,5),
(2,0,14,6,6);
/*!40000 ALTER TABLE `fixed_slots` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `periods` (
  `period_no` int(11) NOT NULL,
  `start_time` varchar(5) NOT NULL,
  `end_time` varchar(5) NOT NULL,
  `break_after` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`period_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `periods` WRITE;
/*!40000 ALTER TABLE `periods` DISABLE KEYS */;
INSERT INTO `periods` VALUES
(1,'07:20','08:40',0),
(2,'08:40','10:00',1),
(3,'10:30','11:50',0),
(4,'11:50','13:00',1),
(5,'14:00','15:20',0),
(6,'15:20','16:40',0);
/*!40000 ALTER TABLE `periods` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(255) NOT NULL,
  `room_type` varchar(40) NOT NULL DEFAULT '',
  `capacity` int(11) NOT NULL DEFAULT 40,
  `room_active` int(11) NOT NULL DEFAULT 1,
  `room_status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rules` (
  `rule_key` varchar(40) NOT NULL,
  `rule_label` varchar(255) NOT NULL,
  `kind` varchar(8) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `sort_no` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rules` WRITE;
/*!40000 ALTER TABLE `rules` DISABLE KEYS */;
INSERT INTO `rules` VALUES
('break_double','Double lessons never straddle a break or lunch','hard',0,7),
('class_clash','A class can only have one lesson at a time','hard',0,2),
('class_gaps','A class\'s free periods fall at the end of the day, not in the middle','soft',4,31),
('fixed_slots','Fixed slots (e.g. Saturday revision) are always honoured and never moved','hard',0,8),
('heavy_back_to_back','Avoid back-to-back demanding subjects for a class','soft',2,32),
('heavy_late','Keep demanding subjects out of the last two periods','soft',1,33),
('learned_double_start','Start double lessons where this school normally starts them (learned)','soft',2,12),
('learned_subject_day','Put each subject on the days of the week this school has used before (learned)','soft',1,11),
('learned_subject_period','Put each subject at the times of day this school has used before (learned)','soft',3,10),
('learned_teacher_period','Follow each teacher\'s habitual time-of-day pattern (learned)','soft',1,13),
('long_gap','Extra penalty for teacher gaps longer than this school normally tolerates','soft',3,22),
('room_clash','A lab / special room hosts one class at a time','hard',0,3),
('room_fit','Lab subjects only go to a matching room with enough capacity','hard',0,5),
('subject_once_per_day','A class does not get the same subject twice in a day (doubles excepted)','soft',10,30),
('teacher_avoid','Honour slots a teacher prefers to avoid','soft',6,20),
('teacher_balance','Spread a teacher\'s lessons evenly over their days','soft',1,23),
('teacher_blocked','Teachers are never placed in slots marked Unavailable','hard',0,4),
('teacher_clash','A teacher can only be in one place at a time','hard',0,1),
('teacher_gaps','Avoid idle periods between a teacher\'s lessons','soft',3,21),
('teacher_load','Respect every teacher\'s maximum lessons per day and per week','hard',0,6);
/*!40000 ALTER TABLE `rules` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('days','Mon,Tue,Wed,Thu,Fri,Sat'),
('manual_hours','40'),
('school_name','Kansanga Senior Secondary School (O-Level)');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES
(1,'Mathematics','MTC','','all',3,1,1,1),
(2,'Physics','PHY','','all',3,1,1,1),
(3,'Chemistry','CHEM','','all',3,1,1,1),
(4,'English Language','ENG','','all',2,1,1,1),
(5,'History','HIST','','all',2,1,1,1),
(6,'Geography','GEO','','all',2,1,1,1),
(7,'Literature in English','LIT','','all',2,1,1,1),
(8,'Luganda','LUG','','all',2,1,1,1),
(9,'ICT / Computer Studies','ICT','','all',2,1,1,1),
(10,'Christian Religious Education','CRE','','all',1,1,1,1),
(11,'Entrepreneurship','ENT','','all',1,1,1,1),
(12,'Kiswahili','KIS','','all',1,1,1,1),
(13,'Art','ART','','all',1,1,1,1),
(14,'Revision (class-supervised)','REV','','all',1,0,1,1);
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teacher_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_slots` (
  `teacher_id` int(11) NOT NULL,
  `day_no` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  `kind` varchar(12) NOT NULL,
  PRIMARY KEY (`teacher_id`,`day_no`,`period_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teacher_slots` WRITE;
/*!40000 ALTER TABLE `teacher_slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_slots` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subjects` (
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  PRIMARY KEY (`teacher_id`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teacher_subjects` WRITE;
/*!40000 ALTER TABLE `teacher_subjects` DISABLE KEYS */;
INSERT INTO `teacher_subjects` VALUES
(1,1),
(2,1),
(3,2),
(4,3),
(5,4),
(6,4),
(7,5),
(8,5),
(9,6),
(10,6),
(11,7),
(12,8),
(13,8),
(14,9),
(15,10),
(16,11),
(17,11),
(18,12),
(19,13),
(20,13);
/*!40000 ALTER TABLE `teacher_subjects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES
(1,'MTC teacher A','MTC1',6,36,0,1,1),
(2,'MTC teacher B','MTC2',6,36,0,1,1),
(3,'PHY teacher A','PHY1',6,36,0,1,1),
(4,'CHEM teacher A','CHEM1',6,36,0,1,1),
(5,'ENG teacher A','ENG1',6,36,0,1,1),
(6,'ENG teacher B','ENG2',6,36,0,1,1),
(7,'HIST teacher A','HIST1',6,36,0,1,1),
(8,'HIST teacher B','HIST2',6,36,0,1,1),
(9,'GEO teacher A','GEO1',6,36,0,1,1),
(10,'GEO teacher B','GEO2',6,36,0,1,1),
(11,'LIT teacher A','LIT1',6,36,0,1,1),
(12,'LUG teacher A','LUG1',6,36,0,1,1),
(13,'LUG teacher B','LUG2',6,36,0,1,1),
(14,'ICT teacher A','ICT1',6,36,0,1,1),
(15,'CRE teacher A','CRE1',6,36,0,1,1),
(16,'ENT teacher A','ENT1',6,36,0,1,1),
(17,'ENT teacher B','ENT2',6,36,0,1,1),
(18,'KIS teacher A','KIS1',6,36,0,1,1),
(19,'ART teacher A','ART1',6,36,0,1,1),
(20,'ART teacher B','ART2',6,36,0,1,1);
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `timetable_lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `slot` (`timetable_id`,`day_no`,`period_no`),
  CONSTRAINT `fk_lesson_tt` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`timetable_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=433 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `timetable_lessons` WRITE;
/*!40000 ALTER TABLE `timetable_lessons` DISABLE KEYS */;
INSERT INTO `timetable_lessons` VALUES
(1,1,1,6,9,NULL,1,1),
(2,1,1,3,4,NULL,1,2),
(3,1,1,5,7,NULL,1,3),
(4,1,1,1,1,NULL,1,4),
(5,1,1,4,5,NULL,1,5),
(6,1,1,8,12,NULL,1,6),
(7,1,2,6,10,NULL,1,1),
(8,1,2,1,1,NULL,1,2),
(9,1,2,8,13,NULL,1,3),
(10,1,2,3,4,NULL,1,4),
(11,1,2,1,1,NULL,1,5),
(12,1,2,11,16,NULL,1,6),
(13,1,3,1,2,NULL,1,1),
(14,1,3,5,8,NULL,1,2),
(15,1,3,5,8,NULL,1,3),
(16,1,3,6,9,NULL,1,4),
(17,1,3,11,16,NULL,1,5),
(18,1,3,4,6,NULL,1,6),
(19,1,4,5,7,NULL,1,1),
(20,1,4,11,17,NULL,1,2),
(21,1,4,1,1,NULL,1,3),
(22,1,4,10,15,NULL,1,4),
(23,1,4,8,13,NULL,1,5),
(24,1,4,2,3,NULL,1,6),
(25,1,1,13,19,NULL,2,1),
(26,1,1,13,19,NULL,2,2),
(27,1,1,7,11,NULL,2,3),
(28,1,1,12,18,NULL,2,4),
(29,1,1,4,5,NULL,2,5),
(30,1,1,8,12,NULL,2,6),
(31,1,2,10,15,NULL,2,1),
(32,1,2,13,20,NULL,2,2),
(33,1,2,10,15,NULL,2,3),
(34,1,2,13,20,NULL,2,4),
(35,1,2,1,1,NULL,2,5),
(36,1,2,8,13,NULL,2,6),
(37,1,3,1,2,NULL,2,1),
(38,1,3,12,18,NULL,2,2),
(39,1,3,13,19,NULL,2,3),
(40,1,3,9,14,NULL,2,4),
(41,1,3,11,16,NULL,2,5),
(42,1,3,11,16,NULL,2,6),
(43,1,4,11,17,NULL,2,1),
(44,1,4,1,1,NULL,2,2),
(45,1,4,9,14,NULL,2,3),
(46,1,4,13,19,NULL,2,4),
(47,1,4,11,17,NULL,2,5),
(48,1,4,4,5,NULL,2,6),
(49,1,1,1,1,NULL,3,1),
(50,1,1,4,5,NULL,3,2),
(51,1,1,5,7,NULL,3,3),
(52,1,1,12,18,NULL,3,4),
(53,1,1,8,12,NULL,3,5),
(54,1,1,2,3,NULL,3,6),
(55,1,2,4,5,NULL,3,1),
(56,1,2,6,10,NULL,3,2),
(57,1,2,3,4,NULL,3,3),
(58,1,2,11,16,NULL,3,4),
(59,1,2,9,14,NULL,3,5),
(60,1,2,10,15,NULL,3,6),
(61,1,3,3,4,NULL,3,1),
(62,1,3,2,3,NULL,3,2),
(63,1,3,7,11,NULL,3,3),
(64,1,3,13,19,NULL,3,4),
(65,1,3,1,2,NULL,3,5),
(66,1,3,4,6,NULL,3,6),
(67,1,4,5,7,NULL,3,1),
(68,1,4,8,13,NULL,3,2),
(69,1,4,1,1,NULL,3,3),
(70,1,4,6,10,NULL,3,4),
(71,1,4,11,17,NULL,3,5),
(72,1,4,3,4,NULL,3,6),
(73,1,1,6,9,NULL,4,1),
(74,1,1,10,15,NULL,4,2),
(75,1,1,13,19,NULL,4,3),
(76,1,1,4,5,NULL,4,4),
(77,1,1,1,1,NULL,4,5),
(78,1,1,5,7,NULL,4,6),
(79,1,2,2,3,NULL,4,1),
(80,1,2,8,13,NULL,4,2),
(81,1,2,1,1,NULL,4,3),
(82,1,2,5,7,NULL,4,4),
(83,1,2,3,4,NULL,4,5),
(84,1,2,4,5,NULL,4,6),
(85,1,3,4,6,NULL,4,1),
(86,1,3,11,16,NULL,4,2),
(87,1,3,6,9,NULL,4,3),
(88,1,3,3,4,NULL,4,4),
(89,1,3,12,18,NULL,4,5),
(90,1,3,8,12,NULL,4,6),
(91,1,4,1,1,NULL,4,1),
(92,1,4,9,14,NULL,4,2),
(93,1,4,2,3,NULL,4,3),
(94,1,4,7,11,NULL,4,4),
(95,1,4,10,15,NULL,4,5),
(96,1,4,13,19,NULL,4,6),
(97,1,1,3,4,NULL,5,1),
(98,1,1,9,14,NULL,5,2),
(99,1,1,8,12,NULL,5,3),
(100,1,1,11,16,NULL,5,4),
(101,1,1,4,5,NULL,5,5),
(102,1,1,6,9,NULL,5,6),
(103,1,2,5,7,NULL,5,1),
(104,1,2,13,20,NULL,5,2),
(105,1,2,4,5,NULL,5,3),
(106,1,2,12,18,NULL,5,4),
(107,1,2,1,1,NULL,5,5),
(108,1,2,2,3,NULL,5,6),
(109,1,3,1,2,NULL,5,1),
(110,1,3,6,9,NULL,5,2),
(111,1,3,10,15,NULL,5,3),
(112,1,3,5,8,NULL,5,4),
(113,1,3,4,6,NULL,5,5),
(114,1,3,9,14,NULL,5,6),
(115,1,4,4,5,NULL,5,1),
(116,1,4,3,4,NULL,5,2),
(117,1,4,8,13,NULL,5,3),
(118,1,4,1,1,NULL,5,4),
(119,1,4,2,3,NULL,5,5),
(120,1,4,6,10,NULL,5,6),
(121,1,1,4,5,NULL,6,1),
(122,1,1,7,11,NULL,6,2),
(123,1,1,1,1,NULL,6,3),
(124,1,1,10,15,NULL,6,4),
(125,1,1,14,0,NULL,6,5),
(126,1,1,14,0,NULL,6,6),
(127,1,2,1,1,NULL,6,1),
(128,1,2,11,16,NULL,6,2),
(129,1,2,6,10,NULL,6,3),
(130,1,2,4,5,NULL,6,4),
(131,1,2,14,0,NULL,6,5),
(132,1,2,14,0,NULL,6,6),
(133,1,3,5,8,NULL,6,1),
(134,1,3,3,4,NULL,6,2),
(135,1,3,1,2,NULL,6,3),
(136,1,3,4,6,NULL,6,4),
(137,1,3,14,0,NULL,6,5),
(138,1,3,14,0,NULL,6,6),
(139,1,4,6,10,NULL,6,1),
(140,1,4,5,7,NULL,6,2),
(141,1,4,4,5,NULL,6,3),
(142,1,4,1,1,NULL,6,4),
(143,1,4,14,0,NULL,6,5),
(144,1,4,14,0,NULL,6,6),
(145,2,1,6,9,NULL,2,1),
(146,2,1,6,9,NULL,1,1),
(147,2,1,6,9,NULL,3,1),
(148,2,1,3,4,NULL,2,4),
(149,2,1,3,4,NULL,6,2),
(150,2,1,5,7,NULL,2,3),
(151,2,1,5,7,NULL,5,2),
(152,2,1,5,7,NULL,4,1),
(153,2,1,1,1,NULL,1,5),
(154,2,1,1,1,NULL,5,1),
(155,2,1,1,1,NULL,2,2),
(156,2,1,1,1,NULL,3,4),
(157,2,1,4,5,NULL,3,5),
(158,2,1,4,5,NULL,6,1),
(159,2,1,4,5,NULL,4,6),
(160,2,1,4,5,NULL,1,6),
(161,2,1,4,5,NULL,5,5),
(162,2,1,4,5,NULL,2,5),
(163,2,1,8,12,NULL,4,2),
(164,2,1,8,12,NULL,3,6),
(165,2,1,8,12,NULL,2,6),
(166,2,1,8,12,NULL,5,6),
(167,2,2,6,10,NULL,3,1),
(168,2,2,6,10,NULL,4,1),
(169,2,2,6,10,NULL,5,1),
(170,2,2,1,1,NULL,5,2),
(171,2,2,1,1,NULL,3,3),
(172,2,2,1,1,NULL,1,4),
(173,2,2,1,1,NULL,4,4),
(174,2,2,1,1,NULL,6,4),
(175,2,2,1,1,NULL,2,1),
(176,2,2,8,13,NULL,6,2),
(177,2,2,8,13,NULL,4,6),
(178,2,2,8,13,NULL,1,3),
(179,2,2,3,4,NULL,1,2),
(180,2,2,3,4,NULL,4,2),
(181,2,2,3,4,NULL,2,3),
(182,2,2,11,16,NULL,1,6),
(183,2,2,11,16,NULL,5,5),
(184,2,2,11,16,NULL,2,5),
(185,2,3,1,2,NULL,4,3),
(186,2,3,1,2,NULL,2,1),
(187,2,3,1,2,NULL,1,3),
(188,2,3,1,2,NULL,3,5),
(189,2,3,1,2,NULL,6,3),
(190,2,3,5,8,NULL,4,1),
(191,2,3,5,8,NULL,1,2),
(192,2,3,5,8,NULL,3,3),
(193,2,3,5,8,NULL,5,3),
(194,2,3,6,9,NULL,5,1),
(195,2,3,6,9,NULL,4,2),
(196,2,3,6,9,NULL,6,4),
(197,2,3,11,16,NULL,6,1),
(198,2,3,11,16,NULL,6,2),
(199,2,3,11,16,NULL,4,5),
(200,2,3,11,16,NULL,1,5),
(201,2,3,4,6,NULL,3,6),
(202,2,3,4,6,NULL,4,6),
(203,2,3,4,6,NULL,5,6),
(204,2,3,4,6,NULL,2,5),
(205,2,3,4,6,NULL,1,4),
(206,2,4,5,7,NULL,1,2),
(207,2,4,5,7,NULL,3,1),
(208,2,4,5,7,NULL,5,1),
(209,2,4,11,17,NULL,1,5),
(210,2,4,11,17,NULL,2,5),
(211,2,4,11,17,NULL,4,5),
(212,2,4,11,17,NULL,5,5),
(213,2,4,1,1,NULL,1,3),
(214,2,4,1,1,NULL,2,3),
(215,2,4,1,1,NULL,5,3),
(216,2,4,1,1,NULL,3,5),
(217,2,4,1,1,NULL,6,3),
(218,2,4,1,1,NULL,4,3),
(219,2,4,10,15,NULL,4,4),
(220,2,4,10,15,NULL,3,3),
(221,2,4,8,13,NULL,2,6),
(222,2,4,8,13,NULL,5,6),
(223,2,4,8,13,NULL,3,6),
(224,2,4,2,3,NULL,4,6),
(225,2,4,2,3,NULL,1,6),
(226,2,4,2,3,NULL,5,2),
(227,2,1,13,19,NULL,6,3),
(228,2,1,13,19,NULL,6,4),
(229,2,1,13,19,NULL,5,4),
(230,2,1,7,11,NULL,3,3),
(231,2,1,7,11,NULL,4,3),
(232,2,1,12,18,NULL,4,4),
(233,2,1,12,18,NULL,1,4),
(234,2,2,10,15,NULL,3,4),
(235,2,2,10,15,NULL,6,3),
(236,2,2,10,15,NULL,2,4),
(237,2,2,13,20,NULL,3,2),
(238,2,2,13,20,NULL,2,2),
(239,2,2,13,20,NULL,4,3),
(240,2,3,12,18,NULL,3,4),
(241,2,3,12,18,NULL,2,4),
(242,2,3,13,19,NULL,3,2),
(243,2,3,13,19,NULL,4,4),
(244,2,3,9,14,NULL,2,2),
(245,2,3,9,14,NULL,5,5),
(246,2,4,9,14,NULL,6,2),
(247,2,4,9,14,NULL,4,2),
(248,2,4,13,19,NULL,2,2),
(249,2,4,13,19,NULL,1,4),
(250,2,4,4,5,NULL,2,4),
(251,2,4,4,5,NULL,3,4),
(252,2,4,4,5,NULL,5,4),
(253,2,1,2,3,NULL,4,5),
(254,2,2,4,5,NULL,5,6),
(255,2,2,4,5,NULL,4,5),
(256,2,2,4,5,NULL,2,6),
(257,2,2,4,5,NULL,1,5),
(258,2,2,9,14,NULL,3,5),
(259,2,3,3,4,NULL,5,2),
(260,2,3,3,4,NULL,3,1),
(261,2,3,3,4,NULL,1,1),
(262,2,3,2,3,NULL,2,6),
(263,2,3,7,11,NULL,2,3),
(264,2,4,6,10,NULL,2,1),
(265,2,4,6,10,NULL,1,1),
(266,2,4,6,10,NULL,6,1),
(267,2,4,3,4,NULL,3,2),
(268,2,4,3,4,NULL,4,1),
(269,2,1,10,15,NULL,1,3),
(270,2,1,10,15,NULL,5,3),
(271,2,2,2,3,NULL,5,3),
(272,2,2,2,3,NULL,3,6),
(273,2,2,5,7,NULL,6,1),
(274,2,2,5,7,NULL,1,1),
(275,2,3,8,12,NULL,1,6),
(276,2,4,7,11,NULL,6,4),
(277,2,1,9,14,NULL,1,2),
(278,2,1,11,16,NULL,3,2),
(279,2,2,12,18,NULL,5,4),
(280,2,3,10,15,NULL,5,4),
(281,2,1,14,0,NULL,6,5),
(282,2,1,14,0,NULL,6,6),
(283,2,2,14,0,NULL,6,5),
(284,2,2,14,0,NULL,6,6),
(285,2,3,14,0,NULL,6,5),
(286,2,3,14,0,NULL,6,6),
(287,2,4,14,0,NULL,6,5),
(288,2,4,14,0,NULL,6,6),
(289,3,1,6,9,NULL,3,2),
(290,3,1,6,9,NULL,5,4),
(291,3,1,6,9,NULL,4,1),
(292,3,1,3,4,NULL,6,4),
(293,3,1,3,4,NULL,2,3),
(294,3,1,5,7,NULL,4,5),
(295,3,1,5,7,NULL,2,4),
(296,3,1,5,7,NULL,3,5),
(297,3,1,1,1,NULL,1,3),
(298,3,1,1,1,NULL,3,1),
(299,3,1,1,1,NULL,5,1),
(300,3,1,1,1,NULL,4,2),
(301,3,1,4,5,NULL,3,6),
(302,3,1,4,5,NULL,2,2),
(303,3,1,4,5,NULL,5,2),
(304,3,1,4,5,NULL,1,6),
(305,3,1,4,5,NULL,4,3),
(306,3,1,4,5,NULL,6,3),
(307,3,1,8,12,NULL,3,4),
(308,3,1,8,12,NULL,5,6),
(309,3,1,8,12,NULL,4,6),
(310,3,1,8,12,NULL,2,6),
(311,3,2,6,10,NULL,2,4),
(312,3,2,6,10,NULL,4,5),
(313,3,2,6,10,NULL,6,3),
(314,3,2,1,1,NULL,4,3),
(315,3,2,1,1,NULL,5,2),
(316,3,2,1,1,NULL,1,2),
(317,3,2,1,1,NULL,6,2),
(318,3,2,1,1,NULL,2,2),
(319,3,2,1,1,NULL,3,3),
(320,3,2,8,13,NULL,1,1),
(321,3,2,8,13,NULL,6,1),
(322,3,2,8,13,NULL,2,6),
(323,3,2,3,4,NULL,3,1),
(324,3,2,3,4,NULL,4,2),
(325,3,2,3,4,NULL,5,3),
(326,3,2,11,16,NULL,5,5),
(327,3,2,11,16,NULL,4,1),
(328,3,2,11,16,NULL,3,2),
(329,3,3,1,2,NULL,5,3),
(330,3,3,1,2,NULL,2,3),
(331,3,3,1,2,NULL,4,2),
(332,3,3,1,2,NULL,3,2),
(333,3,3,1,2,NULL,1,3),
(334,3,3,5,8,NULL,2,5),
(335,3,3,5,8,NULL,5,4),
(336,3,3,5,8,NULL,1,4),
(337,3,3,5,8,NULL,4,4),
(338,3,3,6,9,NULL,2,6),
(339,3,3,6,9,NULL,1,2),
(340,3,3,6,9,NULL,6,4),
(341,3,3,11,16,NULL,6,1),
(342,3,3,11,16,NULL,6,2),
(343,3,3,11,16,NULL,2,1),
(344,3,3,11,16,NULL,3,3),
(345,3,3,4,6,NULL,3,1),
(346,3,3,4,6,NULL,2,4),
(347,3,3,4,6,NULL,1,6),
(348,3,3,4,6,NULL,5,6),
(349,3,3,4,6,NULL,4,6),
(350,3,4,5,7,NULL,1,6),
(351,3,4,5,7,NULL,4,6),
(352,3,4,5,7,NULL,6,2),
(353,3,4,11,17,NULL,3,4),
(354,3,4,11,17,NULL,5,5),
(355,3,4,11,17,NULL,2,5),
(356,3,4,11,17,NULL,4,2),
(357,3,4,1,1,NULL,6,1),
(358,3,4,1,1,NULL,3,2),
(359,3,4,1,1,NULL,1,4),
(360,3,4,1,1,NULL,5,3),
(361,3,4,1,1,NULL,2,1),
(362,3,4,1,1,NULL,4,1),
(363,3,4,10,15,NULL,2,2),
(364,3,4,10,15,NULL,4,3),
(365,3,4,8,13,NULL,3,1),
(366,3,4,8,13,NULL,4,5),
(367,3,4,8,13,NULL,5,4),
(368,3,4,2,3,NULL,2,4),
(369,3,4,2,3,NULL,6,3),
(370,3,4,2,3,NULL,4,4),
(371,3,1,13,19,NULL,6,1),
(372,3,1,13,19,NULL,6,2),
(373,3,1,13,19,NULL,5,3),
(374,3,1,7,11,NULL,1,2),
(375,3,1,7,11,NULL,2,5),
(376,3,1,12,18,NULL,1,1),
(377,3,1,12,18,NULL,5,5),
(378,3,2,10,15,NULL,1,3),
(379,3,2,10,15,NULL,5,6),
(380,3,2,10,15,NULL,3,4),
(381,3,2,13,20,NULL,5,4),
(382,3,2,13,20,NULL,1,6),
(383,3,2,13,20,NULL,3,5),
(384,3,3,12,18,NULL,3,5),
(385,3,3,12,18,NULL,4,5),
(386,3,3,13,19,NULL,4,1),
(387,3,3,13,19,NULL,3,6),
(388,3,3,9,14,NULL,5,1),
(389,3,3,9,14,NULL,3,4),
(390,3,4,9,14,NULL,6,4),
(391,3,4,9,14,NULL,1,5),
(392,3,4,13,19,NULL,2,6),
(393,3,4,13,19,NULL,1,1),
(394,3,4,4,5,NULL,2,3),
(395,3,4,4,5,NULL,5,1),
(396,3,4,4,5,NULL,3,5),
(397,3,1,2,3,NULL,3,3),
(398,3,2,4,5,NULL,1,5),
(399,3,2,4,5,NULL,6,4),
(400,3,2,4,5,NULL,2,1),
(401,3,2,4,5,NULL,4,4),
(402,3,2,9,14,NULL,4,6),
(403,3,3,3,4,NULL,4,3),
(404,3,3,3,4,NULL,2,2),
(405,3,3,3,4,NULL,1,1),
(406,3,3,2,3,NULL,5,2),
(407,3,3,7,11,NULL,5,5),
(408,3,4,6,10,NULL,5,6),
(409,3,4,6,10,NULL,1,3),
(410,3,4,6,10,NULL,3,6),
(411,3,4,3,4,NULL,5,2),
(412,3,4,3,4,NULL,1,2),
(413,3,1,10,15,NULL,4,4),
(414,3,1,10,15,NULL,1,4),
(415,3,2,2,3,NULL,1,4),
(416,3,2,2,3,NULL,2,3),
(417,3,2,5,7,NULL,3,6),
(418,3,2,5,7,NULL,5,1),
(419,3,3,8,12,NULL,1,5),
(420,3,4,7,11,NULL,3,3),
(421,3,1,9,14,NULL,2,1),
(422,3,1,11,16,NULL,1,5),
(423,3,2,12,18,NULL,2,5),
(424,3,3,10,15,NULL,6,3),
(425,3,1,14,0,NULL,6,5),
(426,3,1,14,0,NULL,6,6),
(427,3,2,14,0,NULL,6,5),
(428,3,2,14,0,NULL,6,6),
(429,3,3,14,0,NULL,6,5),
(430,3,3,14,0,NULL,6,6),
(431,3,4,14,0,NULL,6,5),
(432,3,4,14,0,NULL,6,6);
/*!40000 ALTER TABLE `timetable_lessons` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`timetable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
INSERT INTO `timetables` VALUES
(1,'Kansanga O-Level timetable 2026 (as posted)','2026','imported','historical',0,0,0,0,'Transcribed from the posted timetable photo.','2026-10-14 16:31:10'),
(2,'Kansanga generated (learned from posted timetable)','Term 1 2026','generated','draft',-82.1956840142696,0.9236111111111112,0,12.000443935394287,'Learned from 1 past timetable.','2026-10-14 16:31:22'),
(3,'Kansanga generated (generic, no learning)','Term 1 2026','generated','draft',58,0.625,0,12.001476049423218,'Generic baseline.','2026-10-14 16:31:34');
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','$2y$10$8JNMf8iMQYyH9JRexNneQOFJaf7XXDzO0Xqb5TyVFXDXd/8axTl86','','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

