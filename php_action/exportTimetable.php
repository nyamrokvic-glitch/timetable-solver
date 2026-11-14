<?php
/** CSV export of a timetable. Same columns the History import reads, so it round-trips. */
require_once 'core.php';

$id = (int)$_GET['id'];
$tt = db_one($connect, "SELECT * FROM timetables WHERE timetable_id = ?", 'i', array($id));
if(!$tt) { die('Timetable not found'); }
$days = day_names($connect);
$rows = db_all($connect, "SELECT c.class_name, s.subject_name, t.teacher_name, r.room_name, l.day_no, l.period_no
	FROM timetable_lessons l
	INNER JOIN classes c ON c.class_id = l.class_id INNER JOIN subjects s ON s.subject_id = l.subject_id LEFT JOIN teachers t ON t.teacher_id = l.teacher_id
	LEFT JOIN rooms r ON r.room_id = l.room_id
	WHERE l.timetable_id = ? ORDER BY c.class_name, l.day_no, l.period_no", 'i', array($id));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $tt['name']) . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, array('class', 'subject', 'teacher', 'day', 'period', 'room'));
foreach($rows as $r) {
	fputcsv($out, array($r['class_name'], $r['subject_name'], (string)$r['teacher_name'], isset($days[$r['day_no'] - 1]) ? $days[$r['day_no'] - 1] : $r['day_no'], $r['period_no'], $r['room_name']));
}
fclose($out);
