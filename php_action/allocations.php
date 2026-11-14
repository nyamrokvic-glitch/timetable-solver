<?php
/** Allocations (class + subject + teacher + periods): fetch | get | create | edit | remove | copy */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';

function alloc_validate($connect, $classId, $subjectId, $teacherId, $ppw, $doubles, $ignoreId) {
	if(!$classId || !$subjectId) { return 'Class and subject are required'; }
	$sub = db_one($connect, "SELECT needs_teacher FROM subjects WHERE subject_id = ?", 'i', array($subjectId));
	if(!$sub) { return 'Unknown subject'; }
	if(!$sub['needs_teacher'] && $teacherId) { return 'This subject is class-supervised - leave the teacher as (no teacher)'; }
	if($sub['needs_teacher'] && !$teacherId) { return 'Please choose a teacher'; }
	if($ppw < 1) { return 'Periods per week must be at least 1'; }
	if($doubles < 0 || $doubles * 2 > $ppw) { return 'The double lessons need more periods than the ' . $ppw . ' allocated'; }
	$q = $teacherId ? db_one($connect, "SELECT COUNT(*) n FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?", 'ii', array($teacherId, $subjectId)) : array('n' => 1);
	if(!$q['n']) { return 'That teacher is not marked as qualified for this subject (edit the teacher to add it)'; }
	$d = db_one($connect, "SELECT COUNT(*) n FROM allocations WHERE class_id = ? AND subject_id = ? AND alloc_status = 1 AND alloc_id <> ?", 'iii', array($classId, $subjectId, $ignoreId));
	if($d['n']) { return 'This class already has that subject - edit the existing allocation instead'; }
	return '';
}

if($action == 'fetch') {
	$out = array('data' => array());
	$where = ''; $types = ''; $params = array();
	if(!empty($_GET['class_id'])) { $where = ' AND a.class_id = ?'; $types = 'i'; $params[] = (int)$_GET['class_id']; }
	$sql = "SELECT a.*, c.class_name, s.subject_name, s.room_type, t.teacher_name FROM allocations a
		INNER JOIN classes c ON c.class_id = a.class_id AND c.class_status = 1
		INNER JOIN subjects s ON s.subject_id = a.subject_id
		LEFT JOIN teachers t ON t.teacher_id = a.teacher_id
		WHERE a.alloc_status = 1 $where ORDER BY c.class_name, s.subject_name";
	foreach(db_all($connect, $sql, $types, $params) as $r) {
		$out['data'][] = array(h($r['class_name']), h($r['subject_name']), $r['teacher_id'] ? h($r['teacher_name']) : '<span class="text-slate-400">(no teacher)</span>', (int)$r['periods_per_week'],
			$r['doubles'] ? (int)$r['doubles'] . ' double' . ($r['doubles'] > 1 ? 's' : '') : '-', ui_row_buttons($r['alloc_id']));
	}
	json_out($out);
}

if($action == 'get') {
	$row = db_one($connect, "SELECT * FROM allocations WHERE alloc_id = ? AND alloc_status = 1", 'i', array((int)pv('id')));
	json_out($row ? $row : array('success' => false, 'messages' => 'Allocation not found'));
}

if($action == 'create' || $action == 'edit') {
	$cls = (int)pv('class_id'); $sub = (int)pv('subject_id'); $tch = (int)pv('teacher_id');
	$ppw = (int)pv('periods_per_week'); $dbl = (int)pv('doubles');
	$id = $action == 'edit' ? (int)pv('id') : 0;
	$err = alloc_validate($connect, $cls, $sub, $tch, $ppw, $dbl, $id);
	if($err !== '') { json_out(array('success' => false, 'messages' => $err)); }
	if($action == 'create') {
		$ok = db_run($connect, "INSERT INTO allocations (class_id, subject_id, teacher_id, periods_per_week, doubles, alloc_status) VALUES (?,?,?,?,?,1)", 'iiiii', array($cls, $sub, $tch, $ppw, $dbl));
		$msg = 'Successfully Added';
	} else {
		$ok = db_run($connect, "UPDATE allocations SET class_id = ?, subject_id = ?, teacher_id = ?, periods_per_week = ?, doubles = ? WHERE alloc_id = ?", 'iiiiii', array($cls, $sub, $tch, $ppw, $dbl, $id));
		$msg = 'Successfully Updated';
	}
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg : 'Error while saving the allocation'));
}

if($action == 'remove') {
	$ok = db_run($connect, "UPDATE allocations SET alloc_status = 2 WHERE alloc_id = ?", 'i', array((int)pv('id')));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed' : 'Error while removing the allocation'));
}

// copy every allocation of one class to another class (skips subjects the target already has)
if($action == 'copy') {
	$from = (int)pv('from_class'); $to = (int)pv('to_class');
	if(!$from || !$to || $from == $to) { json_out(array('success' => false, 'messages' => 'Choose two different classes')); }
	$n = 0;
	foreach(db_all($connect, "SELECT * FROM allocations WHERE class_id = ? AND alloc_status = 1", 'i', array($from)) as $a) {
		$has = db_one($connect, "SELECT COUNT(*) n FROM allocations WHERE class_id = ? AND subject_id = ? AND alloc_status = 1", 'ii', array($to, $a['subject_id']));
		if($has['n']) { continue; }
		db_run($connect, "INSERT INTO allocations (class_id, subject_id, teacher_id, periods_per_week, doubles, alloc_status) VALUES (?,?,?,?,?,1)", 'iiiii',
			array($to, $a['subject_id'], $a['teacher_id'], $a['periods_per_week'], $a['doubles']));
		$n++;
	}
	json_out(array('success' => true, 'messages' => "Copied $n allocation(s). Review the teacher on each - the same teacher was kept."));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
