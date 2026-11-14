<?php
/**
 * Shared helpers: safe queries, settings, and the loader that turns the
 * database into the "problem" the solver works on.
 */

function h($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function is_admin() {
	return isset($_SESSION['role']) ? $_SESSION['role'] === 'admin' : (isset($_SESSION['userId']) && $_SESSION['userId'] == 1);
}

// admin-only endpoints call this first
function require_admin_json() {
	if(!is_admin()) {
		echo json_encode(array('success' => false, 'messages' => 'Only an administrator can do this'));
		exit;
	}
}

// admin-only pages call this right after including the header
function require_admin_page() {
	if(!is_admin()) { header('location:dashboard.php'); exit; }
}

/** POST value with a default, so a missing field is a validation message, not a PHP warning */
function pv($key, $default = '') {
	return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function json_out($data) {
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

/** Run a prepared SELECT and return all rows as associative arrays. */
function db_all($connect, $sql, $types = '', $params = array()) {
	$stmt = $connect->prepare($sql);
	if(!$stmt) { throw new Exception('SQL error: ' . $connect->error); }
	if($types !== '') { $stmt->bind_param($types, ...$params); }
	$stmt->execute();
	$res = $stmt->get_result();
	$rows = array();
	if($res) { while($r = $res->fetch_assoc()) { $rows[] = $r; } }
	$stmt->close();
	return $rows;
}

function db_one($connect, $sql, $types = '', $params = array()) {
	$rows = db_all($connect, $sql, $types, $params);
	return $rows ? $rows[0] : null;
}

/** Run a prepared INSERT/UPDATE/DELETE. Returns true/false. */
function db_run($connect, $sql, $types = '', $params = array()) {
	$stmt = $connect->prepare($sql);
	if(!$stmt) { return false; }
	if($types !== '') { $stmt->bind_param($types, ...$params); }
	$ok = $stmt->execute();
	$stmt->close();
	return $ok;
}

function get_setting($connect, $key, $default = '') {
	$r = db_one($connect, "SELECT setting_value FROM settings WHERE setting_key = ?", 's', array($key));
	return $r ? $r['setting_value'] : $default;
}

function set_setting($connect, $key, $value) {
	return db_run($connect, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", 'ss', array($key, $value));
}

function day_names($connect) {
	$days = array_values(array_filter(array_map('trim', explode(',', get_setting($connect, 'days', 'Mon,Tue,Wed,Thu,Fri')))));
	return $days ? $days : array('Mon', 'Tue', 'Wed', 'Thu', 'Fri');
}

function period_rows($connect) {
	return db_all($connect, "SELECT period_no, start_time, end_time, break_after FROM periods ORDER BY period_no");
}

/** A stable pastel colour per subject for the timetable grids. */
function subject_hue($subjectId) {
	return (($subjectId * 47) + 15) % 360;
}

function subject_style($subjectId) {
	$hue = subject_hue($subjectId);
	return "background:hsl($hue,75%,92%);border-left:4px solid hsl($hue,60%,48%);color:#1e293b";
}

/**
 * Load everything the solver and validator need.
 * All ids are the database ids; days and periods are 1-based in the DB
 * and 0-based inside the solver.
 */
function load_problem($connect) {
	$P = array();
	$P['days'] = day_names($connect);
	$P['D'] = count($P['days']);
	$P['periods'] = period_rows($connect);
	$P['P'] = count($P['periods']);
	$P['break_after'] = array();
	foreach($P['periods'] as $i => $p) { $P['break_after'][$i] = (int)$p['break_after']; }

	$P['classes'] = array();
	foreach(db_all($connect, "SELECT * FROM classes WHERE class_status = 1 AND class_active = 1 ORDER BY class_name") as $r) {
		$P['classes'][(int)$r['class_id']] = array('name' => $r['class_name'], 'level' => $r['level'], 'students' => (int)$r['students']);
	}
	$P['subjects'] = array();
	foreach(db_all($connect, "SELECT * FROM subjects WHERE subject_status = 1 ORDER BY subject_name") as $r) {
		$P['subjects'][(int)$r['subject_id']] = array('name' => $r['subject_name'], 'code' => $r['subject_code'], 'room_type' => $r['room_type'],
			'room_scope' => $r['room_scope'], 'difficulty' => (int)$r['difficulty'], 'needs_teacher' => (int)$r['needs_teacher']);
	}
	$P['rooms'] = array();
	foreach(db_all($connect, "SELECT * FROM rooms WHERE room_status = 1 AND room_active = 1 ORDER BY room_name") as $r) {
		$P['rooms'][(int)$r['room_id']] = array('name' => $r['room_name'], 'type' => $r['room_type'], 'capacity' => (int)$r['capacity']);
	}
	$P['teachers'] = array();
	foreach(db_all($connect, "SELECT * FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name") as $r) {
		$P['teachers'][(int)$r['teacher_id']] = array('name' => $r['teacher_name'], 'code' => $r['teacher_code'], 'max_day' => (int)$r['max_per_day'],
			'max_week' => (int)$r['max_per_week'], 'part_time' => (int)$r['part_time'], 'blocked' => array(), 'avoid' => array());
	}
	foreach(db_all($connect, "SELECT teacher_id, day_no, period_no, kind FROM teacher_slots") as $r) {
		$t = (int)$r['teacher_id'];
		if(!isset($P['teachers'][$t])) { continue; }
		$d = (int)$r['day_no'] - 1; $p = (int)$r['period_no'] - 1;
		if($d < 0 || $d >= $P['D'] || $p < 0 || $p >= $P['P']) { continue; }
		$P['teachers'][$t][$r['kind'] === 'unavailable' ? 'blocked' : 'avoid'][$d * $P['P'] + $p] = true;
	}
	$P['allocs'] = array();
	foreach(db_all($connect, "SELECT * FROM allocations WHERE alloc_status = 1") as $r) {
		if(!isset($P['classes'][(int)$r['class_id']]) || !isset($P['subjects'][(int)$r['subject_id']])) { continue; }
		// teacher 0 is allowed only for subjects that need no teacher (e.g. Revision)
		if((int)$r['teacher_id'] == 0 ? $P['subjects'][(int)$r['subject_id']]['needs_teacher'] : !isset($P['teachers'][(int)$r['teacher_id']])) { continue; }
		$P['allocs'][] = array('id' => (int)$r['alloc_id'], 'class' => (int)$r['class_id'], 'subject' => (int)$r['subject_id'],
			'teacher' => (int)$r['teacher_id'], 'ppw' => (int)$r['periods_per_week'], 'doubles' => (int)$r['doubles']);
	}
	$P['fixed'] = array();
	foreach(db_all($connect, "SELECT class_id, subject_id, day_no, period_no FROM fixed_slots") as $r) {
		$d = (int)$r['day_no'] - 1; $p = (int)$r['period_no'] - 1;
		if($d < 0 || $d >= $P['D'] || $p < 0 || $p >= $P['P'] || !isset($P['subjects'][(int)$r['subject_id']])) { continue; }
		$P['fixed'][] = array('class' => (int)$r['class_id'], 'subject' => (int)$r['subject_id'], 'd' => $d, 'p' => $p);
	}
	$P['weights'] = array();
	foreach(db_all($connect, "SELECT rule_key, weight FROM rules") as $r) { $P['weights'][$r['rule_key']] = (int)$r['weight']; }
	return $P;
}

/**
 * Pre-flight checks: things that make a full timetable impossible, found
 * before the solver wastes time. Returns a list of [level, message].
 */
function preflight_checks($P) {
	$out = array();
	$S = $P['D'] * $P['P'];
	$classLoad = array(); $teacherLoad = array(); $roomLoad = array();
	foreach($P['allocs'] as $a) {
		$classLoad[$a['class']] = (isset($classLoad[$a['class']]) ? $classLoad[$a['class']] : 0) + $a['ppw'];
		if($a['teacher']) { $teacherLoad[$a['teacher']] = (isset($teacherLoad[$a['teacher']]) ? $teacherLoad[$a['teacher']] : 0) + $a['ppw']; }
		if($a['doubles'] * 2 > $a['ppw']) {
			$out[] = array('error', $P['classes'][$a['class']]['name'] . ' / ' . $P['subjects'][$a['subject']]['name'] . ': ' . $a['doubles'] . ' double(s) need more than the ' . $a['ppw'] . ' periods allocated.');
		}
		$sub = $P['subjects'][$a['subject']];
		if($sub['room_type'] !== '') {
			$need = ($sub['room_scope'] === 'all') ? $a['ppw'] : $a['doubles'] * 2;
			$roomLoad[$sub['room_type']] = (isset($roomLoad[$sub['room_type']]) ? $roomLoad[$sub['room_type']] : 0) + $need;
			$fit = 0;
			foreach($P['rooms'] as $r) { if($r['type'] === $sub['room_type'] && $r['capacity'] >= $P['classes'][$a['class']]['students']) { $fit++; } }
			if($fit == 0 && $need > 0) {
				$out[] = array('error', $P['classes'][$a['class']]['name'] . ' / ' . $sub['name'] . ' needs a "' . $sub['room_type'] . '" room with capacity for ' . $P['classes'][$a['class']]['students'] . ' students, but none exists.');
			}
		}
	}
	foreach($classLoad as $c => $n) {
		if($n > $S) { $out[] = array('error', $P['classes'][$c]['name'] . ' has ' . $n . ' periods a week but only ' . $S . ' slots exist.'); }
	}
	foreach($teacherLoad as $t => $n) {
		$T = $P['teachers'][$t];
		$free = $S - count($T['blocked']);
		if($n > $T['max_week']) { $out[] = array('error', $T['name'] . ' is allocated ' . $n . ' periods but their weekly maximum is ' . $T['max_week'] . '.'); }
		if($n > $free) { $out[] = array('error', $T['name'] . ' is allocated ' . $n . ' periods but is only available for ' . $free . ' slots.'); }
		elseif($n > 0.9 * $free) { $out[] = array('warn', $T['name'] . ' is allocated ' . $n . ' of ' . $free . ' available slots - very tight.'); }
		if($n > $T['max_day'] * $P['D']) { $out[] = array('error', $T['name'] . ': ' . $n . ' periods cannot fit under ' . $T['max_day'] . ' per day.'); }
	}
	foreach($roomLoad as $type => $n) {
		$cnt = 0; foreach($P['rooms'] as $r) { if($r['type'] === $type) { $cnt++; } }
		if($cnt == 0) { $out[] = array('error', 'No room of type "' . $type . '" exists, but ' . $n . ' lab periods need one.'); }
		elseif($n > $cnt * $S) { $out[] = array('error', $n . ' "' . $type . '" periods are needed but ' . $cnt . ' room(s) only offer ' . ($cnt * $S) . '.'); }
		elseif($n > 0.8 * $cnt * $S) { $out[] = array('warn', '"' . $type . '" rooms will be ' . round(100 * $n / ($cnt * $S)) . '% full.'); }
	}
	// fixed slots must belong to an allocation, fit its periods and not collide
	$taken = array();
	foreach($P['fixed'] as $f) {
		$cls = $f['class'] ? array($f['class']) : array_keys($P['classes']);
		foreach($cls as $c) {
			$has = null;
			foreach($P['allocs'] as $a) { if($a['class'] == $c && $a['subject'] == $f['subject']) { $has = $a; } }
			if(!$has) { if($f['class']) { $out[] = array('warn', 'Fixed slot for ' . $P['classes'][$c]['name'] . ' / ' . $P['subjects'][$f['subject']]['name'] . ' has no matching allocation.'); } continue; }
			$k = $c . '|' . $f['d'] . '|' . $f['p'];
			if(isset($taken[$k]) && $taken[$k] != $f['subject']) { $out[] = array('error', $P['classes'][$c]['name'] . ' has two different fixed lessons at ' . $P['days'][$f['d']] . ' P' . ($f['p'] + 1) . '.'); }
			$taken[$k] = $f['subject'];
			if($has['teacher']) {
				$T = $P['teachers'][$has['teacher']];
				if(isset($T['blocked'][$f['d'] * $P['P'] + $f['p']])) { $out[] = array('error', $T['name'] . ' is unavailable at the fixed slot ' . $P['days'][$f['d']] . ' P' . ($f['p'] + 1) . ' (' . $P['subjects'][$f['subject']]['name'] . ').'); }
			}
		}
	}
	if(!$P['allocs']) { $out[] = array('error', 'There are no class / subject / teacher allocations yet.'); }
	return $out;
}


/**
 * Save a set of lessons as a timetable. $lessons: list of
 * array(class, subject, teacher, room|null, day(1-based), period(1-based)).
 * Returns the new timetable_id.
 */
function save_timetable($connect, $name, $term, $source, $status, $lessons, $score = 0, $habit = 0, $unplaced = 0, $seconds = 0, $notes = '') {
	db_run($connect, "INSERT INTO timetables (name, term, source, status, score, habit_match, unplaced, run_seconds, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
		'ssssddids', array($name, $term, $source, $status, $score, $habit, $unplaced, $seconds, $notes));
	$id = (int)$connect->insert_id;
	$chunk = array();
	foreach($lessons as $l) {
		$chunk[] = '(' . $id . ',' . (int)$l['class'] . ',' . (int)$l['subject'] . ',' . (int)$l['teacher'] . ',' . ($l['room'] ? (int)$l['room'] : 'NULL') . ',' . (int)$l['day'] . ',' . (int)$l['period'] . ')';
		if(count($chunk) >= 400) { insert_lesson_chunk($connect, $chunk); $chunk = array(); }
	}
	if($chunk) { insert_lesson_chunk($connect, $chunk); }
	return $id;
}

function insert_lesson_chunk($connect, $chunk) {
	$connect->query("INSERT INTO timetable_lessons (timetable_id, class_id, subject_id, teacher_id, room_id, day_no, period_no) VALUES " . implode(',', $chunk));
}

?>
