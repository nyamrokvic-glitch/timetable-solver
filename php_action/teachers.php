<?php
/** Teachers: fetch | get | create | edit | remove  (soft delete, like the reference project) */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'fetch') {
	$out = array('data' => array());
	$subs = array();
	foreach(db_all($connect, "SELECT ts.teacher_id, s.subject_code FROM teacher_subjects ts INNER JOIN subjects s ON s.subject_id = ts.subject_id WHERE s.subject_status = 1 ORDER BY s.subject_name") as $r) {
		$subs[$r['teacher_id']][] = $r['subject_code'];
	}
	$load = array();
	foreach(db_all($connect, "SELECT teacher_id, SUM(periods_per_week) n FROM allocations WHERE alloc_status = 1 GROUP BY teacher_id") as $r) { $load[$r['teacher_id']] = (int)$r['n']; }
	foreach(db_all($connect, "SELECT * FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name") as $r) {
		$id = (int)$r['teacher_id'];
		$n = isset($load[$id]) ? $load[$id] : 0;
		$over = $n > $r['max_per_week'];
		$out['data'][] = array(
			h($r['teacher_name']),
			h($r['teacher_code']),
			h(isset($subs[$id]) ? implode(', ', $subs[$id]) : '-'),
			($over ? "<span class='text-red-700 font-semibold'>$n</span>" : $n) . ' / ' . (int)$r['max_per_week'],
			(int)$r['max_per_day'],
			$r['part_time'] ? "<label class='label label-info'>Part-time</label>" : "<label class='label label-default'>Full-time</label>",
			ui_status_label($r['teacher_active']),
			ui_row_buttons($id)
		);
	}
	json_out($out);
}

if($action == 'get') {
	$id = (int)pv('id');
	$row = db_one($connect, "SELECT * FROM teachers WHERE teacher_id = ? AND teacher_status = 1", 'i', array($id));
	if(!$row) { json_out(array('success' => false, 'messages' => 'Teacher not found')); }
	$row['subjects'] = array_map(function($r) { return (int)$r['subject_id']; }, db_all($connect, "SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?", 'i', array($id)));
	json_out($row);
}

if($action == 'create' || $action == 'edit') {
	$valid = array('success' => false, 'messages' => '');
	$name = trim(pv('teacher_name'));
	$code = strtoupper(trim(pv('teacher_code')));
	$maxDay = (int)pv('max_per_day'); $maxWeek = (int)pv('max_per_week');
	$part = !empty($_POST['part_time']) ? 1 : 0;
	$active = (int)pv('teacher_active');
	$subjects = isset($_POST['subjects']) ? array_map('intval', $_POST['subjects']) : array();

	if($name === '') { json_out(array('success' => false, 'messages' => 'Teacher name is required')); }
	if($maxDay < 1 || $maxWeek < 1) { json_out(array('success' => false, 'messages' => 'Maximum lessons per day / week must be at least 1')); }

	if($action == 'create') {
		$ok = db_run($connect, "INSERT INTO teachers (teacher_name, teacher_code, max_per_day, max_per_week, part_time, teacher_active, teacher_status) VALUES (?,?,?,?,?,?,1)",
			'ssiiii', array($name, $code, $maxDay, $maxWeek, $part, $active));
		$id = (int)$connect->insert_id;
		$msg = 'Successfully Added';
	} else {
		$id = (int)pv('id');
		$ok = db_run($connect, "UPDATE teachers SET teacher_name = ?, teacher_code = ?, max_per_day = ?, max_per_week = ?, part_time = ?, teacher_active = ? WHERE teacher_id = ?",
			'ssiiiii', array($name, $code, $maxDay, $maxWeek, $part, $active, $id));
		$msg = 'Successfully Updated';
	}
	if($ok) {
		db_run($connect, "DELETE FROM teacher_subjects WHERE teacher_id = ?", 'i', array($id));
		foreach(array_unique($subjects) as $s) { db_run($connect, "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?,?)", 'ii', array($id, $s)); }
	}
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg : 'Error while saving the teacher'));
}

if($action == 'remove') {
	$id = (int)pv('id');
	$n = db_one($connect, "SELECT COUNT(*) n FROM allocations WHERE teacher_id = ? AND alloc_status = 1", 'i', array($id));
	if($n['n'] > 0) { json_out(array('success' => false, 'messages' => 'This teacher still has ' . $n['n'] . ' class allocation(s). Re-assign or remove them first.')); }
	$ok = db_run($connect, "UPDATE teachers SET teacher_status = 2 WHERE teacher_id = ?", 'i', array($id));
	db_run($connect, "DELETE FROM teacher_slots WHERE teacher_id = ?", 'i', array($id));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed' : 'Error while removing the teacher'));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
