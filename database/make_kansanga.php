<?php
/**
 * Builds the Kansanga Senior Secondary School (O-Level) data set from the
 * timetable the school actually posted (database/kansanga/kansanga_timetable_2026.csv).
 *
 *   php database/make_kansanga.php
 *
 * What it does (the "integration"):
 *   1. Sets the school's real shape: Mon-Sat, 6 periods, breaks after P2 and P4.
 *   2. Infers, from the posted timetable, each class's periods per week per subject
 *      and where double lessons are used.
 *   3. Infers the MINIMUM teacher pool per subject (two classes taking the same subject
 *      in the same slot need two teachers). The poster has no teacher names, so these are
 *      placeholders ("MTC teacher A") for the staff to rename in the Teachers page.
 *   4. Pins Saturday afternoon revision (REV, no teacher) as fixed slots.
 *   5. Imports the posted timetable as a HISTORICAL timetable the solver learns from,
 *      checks it with the independent validator, then generates a new one.
 *
 * It EMPTIES the data tables first (users and rules are kept).
 */
if(PHP_SAPI !== 'cli') { die('Run from the command line: php database/make_kansanga.php'); }
chdir(__DIR__ . '/..');
require_once 'php_action/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/learner.php';
require_once 'includes/solver.php';
require_once 'includes/validator.php';

foreach(array('timetable_lessons', 'timetables', 'allocations', 'fixed_slots', 'teacher_slots', 'teacher_subjects', 'teachers', 'rooms', 'classes', 'subjects') as $t) {
	$connect->query("DELETE FROM $t"); $connect->query("ALTER TABLE $t AUTO_INCREMENT = 1");
}
set_setting($connect, 'school_name', 'Kansanga Senior Secondary School (O-Level)');
set_setting($connect, 'days', 'Mon,Tue,Wed,Thu,Fri,Sat');
$connect->query("DELETE FROM periods");
foreach(array(array(1, '07:20', '08:40', 0), array(2, '08:40', '10:00', 1), array(3, '10:30', '11:50', 0), array(4, '11:50', '13:00', 1), array(5, '14:00', '15:20', 0), array(6, '15:20', '16:40', 0)) as $p) {
	db_run($connect, "INSERT INTO periods (period_no, start_time, end_time, break_after) VALUES (?,?,?,?)", 'issi', $p);
}

// ---- read the posted timetable ----
$rows = array();
$fh = fopen('database/kansanga/kansanga_timetable_2026.csv', 'r'); fgetcsv($fh);
while(($r = fgetcsv($fh)) !== false) { if(count($r) >= 4) { $rows[] = array('class' => $r[0], 'subject' => $r[1], 'day' => $r[2], 'period' => (int)$r[3]); } }
fclose($fh);
$dayNo = array('Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6);

// ---- subjects: code => [name, difficulty, needs_teacher] ----
$subjects = array(
	'MTC' => array('Mathematics', 3, 1), 'PHY' => array('Physics', 3, 1), 'CHEM' => array('Chemistry', 3, 1),
	'ENG' => array('English Language', 2, 1), 'HIST' => array('History', 2, 1), 'GEO' => array('Geography', 2, 1),
	'LIT' => array('Literature in English', 2, 1), 'LUG' => array('Luganda', 2, 1), 'ICT' => array('ICT / Computer Studies', 2, 1),
	'CRE' => array('Christian Religious Education', 1, 1), 'ENT' => array('Entrepreneurship', 1, 1), 'KIS' => array('Kiswahili', 1, 1),
	'ART' => array('Art', 1, 1), 'REV' => array('Revision (class-supervised)', 1, 0),
);
$sid = array();
foreach($subjects as $code => $s) {
	db_run($connect, "INSERT INTO subjects (subject_name, subject_code, difficulty, needs_teacher) VALUES (?,?,?,?)", 'ssii', array($s[0], $code, $s[1], $s[2]));
	$sid[$code] = $connect->insert_id;
}
$cid = array();
foreach(array('S.1', 'S.2', 'S.3', 'S.4') as $c) {
	db_run($connect, "INSERT INTO classes (class_name, level, students) VALUES (?,?,40)", 'ss', array($c, $c));
	$cid[$c] = $connect->insert_id;
}

// ---- periods per week, doubles, simultaneity ----
$count = array(); $slotMap = array(); $cell = array();
foreach($rows as $r) {
	$k = $r['class'] . '|' . $r['subject']; $count[$k] = (isset($count[$k]) ? $count[$k] : 0) + 1;
	$slotMap[$r['subject']][$dayNo[$r['day']] . '|' . $r['period']][] = $r['class'];
	$cell[$r['class']][$dayNo[$r['day']]][$r['period']] = $r['subject'];
}
$breakAfter = array(2 => true, 4 => true);
$doubles = array();
foreach($cell as $c => $days) { foreach($days as $d => $per) { foreach($per as $p => $s) {
	if(isset($per[$p + 1]) && $per[$p + 1] === $s && empty($breakAfter[$p]) && !(isset($per[$p - 1]) && $per[$p - 1] === $s && empty($breakAfter[$p - 1]))) {
		$doubles[$c . '|' . $s] = (isset($doubles[$c . '|' . $s]) ? $doubles[$c . '|' . $s] : 0) + 1;
	}
} } }

// ---- minimum teacher pool per subject: colour the "same subject, same slot" conflict graph ----
$teacherOf = array(); $pool = array();
foreach($subjects as $code => $s) {
	if(!$s[2]) { continue; }
	$cls = array(); foreach($count as $k => $n) { list($c, $sc) = explode('|', $k); if($sc === $code) { $cls[] = $c; } }
	sort($cls);
	$conf = array();
	foreach($slotMap[$code] as $list) { foreach($list as $a) { foreach($list as $b) { if($a !== $b) { $conf[$a][$b] = true; } } } }
	$colour = array();
	foreach($cls as $c) {
		$used = array(); if(isset($conf[$c])) { foreach($conf[$c] as $o => $_) { if(isset($colour[$o])) { $used[$colour[$o]] = true; } } }
		$k = 0; while(isset($used[$k])) { $k++; }
		$colour[$c] = $k;
	}
	$pool[$code] = $cls ? max($colour) + 1 : 0;
	foreach($colour as $c => $k) { $teacherOf[$c . '|' . $code] = $code . ($k + 1); }
}
$tid = array();
foreach($teacherOf as $key => $tc) {
	if(isset($tid[$tc])) { continue; }
	preg_match('/^([A-Z]+)(\d+)$/', $tc, $m);
	$name = $m[1] . ' teacher ' . chr(64 + (int)$m[2]);
	db_run($connect, "INSERT INTO teachers (teacher_name, teacher_code, max_per_day, max_per_week) VALUES (?,?,6,36)", 'ss', array($name, $tc));
	$tid[$tc] = $connect->insert_id;
	db_run($connect, "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?,?)", 'ii', array($tid[$tc], $sid[$m[1]]));
}
foreach($count as $k => $n) {
	list($c, $sc) = explode('|', $k);
	$t = isset($teacherOf[$k]) ? $tid[$teacherOf[$k]] : 0;
	db_run($connect, "INSERT INTO allocations (class_id, subject_id, teacher_id, periods_per_week, doubles) VALUES (?,?,?,?,?)", 'iiiii',
		array($cid[$c], $sid[$sc], $t, $n, isset($doubles[$k]) ? $doubles[$k] : 0));
}
// ---- fixed slots: Saturday afternoon revision for every class ----
foreach($rows as $r) { if($r['subject'] === 'REV') { break; } }
$seen = array();
foreach($rows as $r) {
	if($r['subject'] !== 'REV') { continue; }
	$key = $dayNo[$r['day']] . '|' . $r['period'];
	if(isset($seen[$key])) { continue; } $seen[$key] = 1;
	db_run($connect, "INSERT INTO fixed_slots (class_id, subject_id, day_no, period_no) VALUES (0,?,?,?)", 'iii', array($sid['REV'], $dayNo[$r['day']], $r['period']));
}
echo "Teacher pool inferred: " . json_encode($pool) . " = " . count($tid) . " placeholder teachers\n";
echo "Allocations: " . count($count) . ", doubles: " . json_encode($doubles) . "\n";

// ---- import the posted timetable as history ----
$P = load_problem($connect);
foreach(preflight_checks($P) as $x) { echo "preflight " . $x[0] . ": " . $x[1] . "\n"; }
$lessons = array();
foreach($rows as $r) {
	$k = $r['class'] . '|' . $r['subject'];
	$lessons[] = array('class' => $cid[$r['class']], 'subject' => $sid[$r['subject']], 'teacher' => isset($teacherOf[$k]) ? $tid[$teacherOf[$k]] : 0,
		'room' => null, 'day' => $dayNo[$r['day']], 'period' => $r['period']);
}
$hid = save_timetable($connect, 'Kansanga O-Level timetable 2026 (as posted)', '2026', 'imported', 'historical', $lessons, 0, 0, 0, 0, 'Transcribed from the posted timetable photo.');
$v = validate_timetable($connect, $hid, $P);
echo "Posted timetable: " . $v['stats']['lessons'] . " lessons, hard issues " . $v['stats']['hard'] . ", teacher idle " . $v['stats']['teacher_gap_periods'] . ", class gaps " . $v['stats']['class_gap_periods'] . "\n";
foreach(array_slice($v['hard'], 0, 8) as $h) { echo "  HARD: " . $h[1] . "\n"; }

// ---- generate with learning, and a generic baseline ----
$model = Learner::fromDb($connect, $P['D'], $P['P']);
$pairs = function($ls) { return array_map(function($l) { return array($l['subject'], $l['period'] - 1); }, $ls); };
$limit = 12;
$s1 = new TimetableSolver($P, $model); $r1 = $s1->solve($limit, 7);
$id1 = save_timetable($connect, 'Kansanga generated (learned from posted timetable)', 'Term 1 2026', 'generated', 'draft', $r1['lessons'], $r1['cost'], $model->habitMatch($pairs($r1['lessons'])), count($r1['unplaced']), $r1['seconds'], 'Learned from 1 past timetable.');
$v1 = validate_timetable($connect, $id1, $P);
$s2 = new TimetableSolver($P, null); $r2 = $s2->solve($limit, 7);
$id2 = save_timetable($connect, 'Kansanga generated (generic, no learning)', 'Term 1 2026', 'generated', 'draft', $r2['lessons'], $r2['cost'], $model->habitMatch($pairs($r2['lessons'])), count($r2['unplaced']), $r2['seconds'], 'Generic baseline.');
$v2 = validate_timetable($connect, $id2, $P);
printf("Learned : %d lessons, unplaced %d, hard %d, habit match %.1f%%, teacher idle %d, class gaps %d\n", count($r1['lessons']), count($r1['unplaced']), $v1['stats']['hard'], 100 * $model->habitMatch($pairs($r1['lessons'])), $v1['stats']['teacher_gap_periods'], $v1['stats']['class_gap_periods']);
printf("Generic : %d lessons, unplaced %d, hard %d, habit match %.1f%%, teacher idle %d, class gaps %d\n", count($r2['lessons']), count($r2['unplaced']), $v2['stats']['hard'], 100 * $model->habitMatch($pairs($r2['lessons'])), $v2['stats']['teacher_gap_periods'], $v2['stats']['class_gap_periods']);
foreach(array_slice($v1['hard'], 0, 8) as $h) { echo "  HARD: " . $h[1] . "\n"; }
echo "Done.\n";
