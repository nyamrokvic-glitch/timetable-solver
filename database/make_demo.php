<?php
/**
 * Builds the demo data set: a fictional secondary school plus three past
 * ("historical") timetables that follow realistic school habits, so the
 * solver has something to learn from.
 *
 *   php database/make_demo.php
 *
 * It EMPTIES the data tables first (users, rules and settings are kept).
 * The result is exported as "timetable with demo data.sql".
 */
if(PHP_SAPI !== 'cli') { die('Run from the command line: php database/make_demo.php'); }

chdir(__DIR__ . '/..');
require_once 'php_action/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/learner.php';
require_once 'includes/solver.php';

foreach(array('timetable_lessons', 'timetables', 'allocations', 'teacher_slots', 'teacher_subjects', 'teachers', 'rooms', 'classes', 'subjects') as $t) {
	$connect->query("DELETE FROM $t");
	$connect->query("ALTER TABLE $t AUTO_INCREMENT = 1");
}
set_setting($connect, 'school_name', 'Demo Secondary School');

// ---- subjects: code, name, room_type, room_scope, difficulty ----
$subjects = array(
	'ENG'  => array('English Language', '', 'all', 2),
	'MATH' => array('Mathematics', '', 'all', 3),
	'PHY'  => array('Physics', 'physics_lab', 'doubles', 3),
	'CHEM' => array('Chemistry', 'chem_lab', 'doubles', 3),
	'BIO'  => array('Biology', 'bio_lab', 'doubles', 2),
	'HIST' => array('History', '', 'all', 2),
	'GEO'  => array('Geography', '', 'all', 2),
	'CRE'  => array('Christian Religious Education', '', 'all', 1),
	'ENT'  => array('Entrepreneurship', '', 'all', 1),
	'ICT'  => array('Computer Studies', 'computer_lab', 'all', 2),
	'PE'   => array('Physical Education', 'field', 'all', 1),
	'KIS'  => array('Kiswahili', '', 'all', 1),
);
$sid = array();
foreach($subjects as $code => $s) {
	db_run($connect, "INSERT INTO subjects (subject_name, subject_code, room_type, room_scope, difficulty) VALUES (?,?,?,?,?)", 'ssssi', array($s[0], $code, $s[1], $s[2], $s[3]));
	$sid[$code] = $connect->insert_id;
}

// ---- classes ----
$classes = array('S1A' => 44, 'S1B' => 42, 'S2A' => 40, 'S2B' => 38, 'S3A' => 41, 'S3B' => 39, 'S4A' => 36, 'S4B' => 35);
$cid = array();
foreach($classes as $name => $n) {
	db_run($connect, "INSERT INTO classes (class_name, level, students) VALUES (?,?,?)", 'ssi', array($name, substr($name, 0, 2), $n));
	$cid[$name] = $connect->insert_id;
}

// ---- rooms ----
$rooms = array(
	array('Physics Lab', 'physics_lab', 45), array('Chemistry Lab', 'chem_lab', 45), array('Biology Lab', 'bio_lab', 45),
	array('Computer Lab 1', 'computer_lab', 45), array('Computer Lab 2', 'computer_lab', 40),
	array('Sports Field A', 'field', 200), array('Sports Field B', 'field', 200),
);
foreach($rooms as $r) { db_run($connect, "INSERT INTO rooms (room_name, room_type, capacity) VALUES (?,?,?)", 'ssi', $r); }

// ---- teachers: name, code, max/day, max/week, part_time, subjects ----
$teachers = array(
	'OKE' => array('Okello James', 6, 26, 0, array('ENG')),
	'NAM' => array('Namukasa Grace', 6, 26, 0, array('ENG', 'CRE')),
	'MUG' => array('Mugisha Peter', 6, 26, 0, array('MATH')),
	'NAK' => array('Nakato Sarah', 6, 28, 0, array('MATH')),
	'SSE' => array('Ssemakula David', 6, 26, 0, array('PHY', 'ICT', 'MATH')),
	'TUM' => array('Tumusiime Ruth', 6, 26, 0, array('PHY')),
	'BYA' => array('Byaruhanga John', 6, 26, 0, array('CHEM')),
	'AKE' => array('Akello Mary', 6, 26, 0, array('CHEM', 'BIO')),
	'NAB' => array('Nabirye Joyce', 6, 26, 0, array('BIO')),
	'KAT' => array('Kato Moses', 6, 28, 0, array('HIST', 'CRE')),
	'ATI' => array('Atim Florence', 6, 28, 0, array('GEO', 'HIST')),
	'LWA' => array('Lwanga Joseph', 6, 26, 0, array('CRE', 'ENT')),
	'NAN' => array('Nansubuga Betty', 6, 26, 0, array('ENT', 'KIS')),
	'OPI' => array('Opio Daniel', 6, 26, 0, array('ICT', 'PE')),
	'NAL' => array('Nalubega Ann', 6, 12, 1, array('KIS')),
);
$tid = array();
foreach($teachers as $code => $t) {
	db_run($connect, "INSERT INTO teachers (teacher_name, teacher_code, max_per_day, max_per_week, part_time) VALUES (?,?,?,?,?)", 'ssiii', array($t[0], $code, $t[1], $t[2], $t[3]));
	$tid[$code] = $connect->insert_id;
	foreach($t[4] as $sc) { db_run($connect, "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?,?)", 'ii', array($tid[$code], $sid[$sc])); }
}

// ---- allocations: [class list, subject, teacher, periods per week, doubles] ----
$lower = array('S1A', 'S1B', 'S2A', 'S2B');
$upper = array('S3A', 'S3B', 'S4A', 'S4B');
$all = array_merge($lower, $upper);
$alloc = array(
	array($lower, 'ENG', 'OKE', 5, 0), array($upper, 'ENG', 'NAM', 5, 0),
	array($lower, 'MATH', 'MUG', 5, 0), array($upper, 'MATH', 'NAK', 6, 1),
	array($lower, 'PHY', 'SSE', 3, 1), array($upper, 'PHY', 'TUM', 4, 1),
	array(array('S1A', 'S1B', 'S2A', 'S2B', 'S3A', 'S3B'), 'CHEM', 'BYA', 0, 0),   // placeholder, set below
	array($all, 'HIST', 'KAT', 3, 0), array($all, 'GEO', 'ATI', 3, 0),
	array($all, 'CRE', 'LWA', 2, 0), array($all, 'ENT', 'NAN', 2, 0),
	array($lower, 'KIS', 'NAL', 2, 0),
	array($lower, 'PE', 'OPI', 2, 1), array($upper, 'PE', 'OPI', 1, 0),
	array($upper, 'ICT', 'OPI', 2, 1), array($lower, 'ICT', 'SSE', 2, 1),
);
array_splice($alloc, 6, 1, array(
	array(array('S1A', 'S1B', 'S2A', 'S2B'), 'CHEM', 'BYA', 3, 1),
	array(array('S3A', 'S3B'), 'CHEM', 'BYA', 4, 1),
	array(array('S4A', 'S4B'), 'CHEM', 'AKE', 4, 1),
	array(array('S1A', 'S1B', 'S2A', 'S2B'), 'BIO', 'NAB', 3, 1),
	array(array('S3A', 'S3B'), 'BIO', 'NAB', 4, 1),
	array(array('S4A', 'S4B'), 'BIO', 'AKE', 4, 1)
));
foreach($alloc as $a) {
	foreach($a[0] as $cn) {
		db_run($connect, "INSERT INTO allocations (class_id, subject_id, teacher_id, periods_per_week, doubles) VALUES (?,?,?,?,?)", 'iiiii',
			array($cid[$cn], $sid[$a[1]], $tid[$a[2]], $a[3], $a[4]));
	}
}

// ---- teacher availability ----
$slot = function($teacher, $d, $p, $kind) use ($connect, $tid) {
	db_run($connect, "INSERT INTO teacher_slots (teacher_id, day_no, period_no, kind) VALUES (?,?,?,?)", 'iiis', array($tid[$teacher], $d, $p, $kind));
};
$slot('NAM', 1, 1, 'unavailable'); $slot('NAM', 1, 2, 'unavailable');            // Namukasa: late on Mondays
foreach(array(1, 3, 5) as $d) { for($p = 1; $p <= 8; $p++) { $slot('NAL', $d, $p, 'unavailable'); } }   // part-time: Tue & Thu only
$slot('KAT', 5, 7, 'avoid'); $slot('KAT', 5, 8, 'avoid');                         // Kato: no late Fridays
for($d = 1; $d <= 5; $d++) { $slot('ATI', $d, 1, 'avoid'); }                      // Atim: no first period

// ---- the school's habits (used ONLY to synthesise past timetables) ----
// cost per lesson-period: lower = the school likes it more
$habit = function($subjectId, $d, $p, $size) use ($sid) {
	$code = array_search($subjectId, $sid);
	$late = $p >= 6;            // P7, P8
	$c = 0.0;
	for($k = 0; $k < $size; $k++) {
		$pp = $p + $k; $lt = $pp >= 6;
		switch($code) {
			case 'MATH': case 'ENG': $c += $lt ? 4 : ($pp >= 3 ? 1 : -1); break;
			case 'PHY': case 'CHEM': case 'BIO': $c += $size == 1 ? ($lt ? 2 : 0) : 0; break;
			case 'PE': $c += $lt ? -1 : 4; break;
			case 'HIST': case 'GEO': $c += $lt ? 0.5 : 0; break;
			case 'CRE': case 'ENT': case 'KIS': $c += $lt ? -0.5 : 0.5; break;
			case 'ICT': $c += $lt ? -0.5 : 0.5; break;
		}
		if($d == 4 && $lt && in_array($code, array('MATH', 'ENG', 'PHY', 'CHEM'))) { $c += 4; }   // never heavy on Friday afternoon
		if($d == 4 && $lt && in_array($code, array('CRE', 'PE', 'ENT'))) { $c -= 1.5; }          // Friday afternoon belongs to these
	}
	if($size == 2 && in_array($code, array('PHY', 'CHEM', 'BIO'))) { $c += ($p == 3 || $p == 4) ? -3 : 2; }   // practicals start P4/P5
	if($size == 2 && $code === 'PE') { $c += $late ? -2 : 3; }
	if($d == 0 && $p == 0 && $code === 'ENG') { $c -= 1; }
	return $c;
};

$P = load_problem($connect);
$problems = preflight_checks($P);
foreach($problems as $pr) { echo strtoupper($pr[0]) . ': ' . $pr[1] . "\n"; }

$terms = array('Term 1 2025', 'Term 2 2025', 'Term 3 2025');
foreach($terms as $i => $term) {
	$solver = new TimetableSolver($P, null, $habit);
	$res = $solver->solve(14, 1000 + $i * 17);
	echo "$term: " . count($res['lessons']) . " lessons, unplaced " . count($res['unplaced']) . ", cost " . round($res['cost'], 1) . ", " . round($res['seconds'], 1) . "s\n";
	if($res['unplaced']) { die("Could not place everything - fix the demo data.\n"); }
	$id = save_timetable($connect, "Accepted timetable - $term", $term, 'imported', 'historical', $res['lessons'], $res['cost'], 0, 0, $res['seconds'], 'Demo history (fictional).');
	echo "  saved as #$id\n";
}
echo "Done.\n";
