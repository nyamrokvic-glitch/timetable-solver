<?php
/** Classes: fetch | get | create | edit | remove */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'fetch') {
	$out = array('data' => array());
	$load = array();
	foreach(db_all($connect, "SELECT class_id, SUM(periods_per_week) n FROM allocations WHERE alloc_status = 1 GROUP BY class_id") as $r) { $load[$r['class_id']] = (int)$r['n']; }
	$days = day_names($connect); $slots = count($days) * count(period_rows($connect));
	foreach(db_all($connect, "SELECT * FROM classes WHERE class_status = 1 ORDER BY class_name") as $r) {
		$n = isset($load[$r['class_id']]) ? $load[$r['class_id']] : 0;
		$out['data'][] = array(h($r['class_name']), h($r['level']), (int)$r['students'], $n . ' / ' . $slots, ui_status_label($r['class_active']), ui_row_buttons($r['class_id']));
	}
	json_out($out);
}

if($action == 'get') {
	$row = db_one($connect, "SELECT * FROM classes WHERE class_id = ? AND class_status = 1", 'i', array((int)pv('id')));
	json_out($row ? $row : array('success' => false, 'messages' => 'Class not found'));
}

if($action == 'create' || $action == 'edit') {
	$name = trim(pv('class_name')); $level = trim(pv('level'));
	$students = (int)pv('students'); $active = (int)pv('class_active');
	if($name === '') { json_out(array('success' => false, 'messages' => 'Class name is required')); }
	if($students < 1) { json_out(array('success' => false, 'messages' => 'Number of students must be at least 1')); }
	if($action == 'create') {
		$ok = db_run($connect, "INSERT INTO classes (class_name, level, students, class_active, class_status) VALUES (?,?,?,?,1)", 'ssii', array($name, $level, $students, $active));
		$msg = 'Successfully Added';
	} else {
		$ok = db_run($connect, "UPDATE classes SET class_name = ?, level = ?, students = ?, class_active = ? WHERE class_id = ?", 'ssiii', array($name, $level, $students, $active, (int)pv('id')));
		$msg = 'Successfully Updated';
	}
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg : 'Error while saving the class'));
}

if($action == 'remove') {
	$id = (int)pv('id');
	$ok = db_run($connect, "UPDATE classes SET class_status = 2 WHERE class_id = ?", 'i', array($id));
	db_run($connect, "UPDATE allocations SET alloc_status = 2 WHERE class_id = ?", 'i', array($id));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed (its allocations were removed too)' : 'Error while removing the class'));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
