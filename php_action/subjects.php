<?php
/** Subjects: fetch | get | create | edit | remove */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$diffLabels = array(1 => 'Light', 2 => 'Medium', 3 => 'Demanding');

if($action == 'fetch') {
	$out = array('data' => array());
	foreach(db_all($connect, "SELECT * FROM subjects WHERE subject_status = 1 ORDER BY subject_name") as $r) {
		$room = $r['room_type'] === '' ? '<span class="text-slate-400">ordinary classroom</span>' : h($r['room_type']) . ' <small class="text-slate-500">(' . ($r['room_scope'] == 'all' ? 'all lessons' : 'doubles only') . ')</small>';
		$out['data'][] = array(h($r['subject_name']), h($r['subject_code']), $room, $diffLabels[(int)$r['difficulty']], ui_status_label($r['subject_active']) . ($r['needs_teacher'] ? '' : ' <small class="text-slate-500">(no teacher)</small>'), ui_row_buttons($r['subject_id']));
	}
	json_out($out);
}

if($action == 'get') {
	$row = db_one($connect, "SELECT * FROM subjects WHERE subject_id = ? AND subject_status = 1", 'i', array((int)pv('id')));
	if($row) { $row['needs_teacher'] = $row['needs_teacher'] ? 1 : 2; }
	json_out($row ? $row : array('success' => false, 'messages' => 'Subject not found'));
}

if($action == 'create' || $action == 'edit') {
	$name = trim(pv('subject_name'));
	$code = strtoupper(trim(pv('subject_code')));
	$room = trim(pv('room_type'));
	$room = preg_replace('/[^a-z0-9_]+/', '_', strtolower($room));
	$scope = pv('room_scope') === 'doubles' ? 'doubles' : 'all';
	$diff = max(1, min(3, (int)pv('difficulty')));
	$active = (int)pv('subject_active');
	$needs = pv('needs_teacher') === '2' ? 0 : 1;
	if($name === '') { json_out(array('success' => false, 'messages' => 'Subject name is required')); }
	if($action == 'create') {
		$ok = db_run($connect, "INSERT INTO subjects (subject_name, subject_code, room_type, room_scope, difficulty, needs_teacher, subject_active, subject_status) VALUES (?,?,?,?,?,?,?,1)", 'ssssiii', array($name, $code, $room, $scope, $diff, $needs, $active));
		$msg = 'Successfully Added';
	} else {
		$ok = db_run($connect, "UPDATE subjects SET subject_name = ?, subject_code = ?, room_type = ?, room_scope = ?, difficulty = ?, needs_teacher = ?, subject_active = ? WHERE subject_id = ?", 'ssssiiii', array($name, $code, $room, $scope, $diff, $needs, $active, (int)pv('id')));
		$msg = 'Successfully Updated';
	}
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg : 'Error while saving the subject'));
}

if($action == 'remove') {
	$id = (int)pv('id');
	$n = db_one($connect, "SELECT COUNT(*) n FROM allocations WHERE subject_id = ? AND alloc_status = 1", 'i', array($id));
	if($n['n'] > 0) { json_out(array('success' => false, 'messages' => 'This subject is still used in ' . $n['n'] . ' allocation(s). Remove those first.')); }
	$ok = db_run($connect, "UPDATE subjects SET subject_status = 2 WHERE subject_id = ?", 'i', array($id));
	db_run($connect, "DELETE FROM teacher_subjects WHERE subject_id = ?", 'i', array($id));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed' : 'Error while removing the subject'));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
