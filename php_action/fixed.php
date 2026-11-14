<?php
/** Fixed slots (e.g. Saturday revision): fetch | get | create | edit | remove */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$days = day_names($connect);

function fixed_check($connect, $cls, $sub, $day, $per, $ignore) {
	$D = count(day_names($connect)); $P = count(period_rows($connect));
	if(!$sub) { return 'Please choose a subject'; }
	if($day < 1 || $day > $D) { return 'Please choose a day'; }
	if($per < 1 || $per > $P) { return 'Please choose a period'; }
	$x = db_one($connect, "SELECT COUNT(*) n FROM fixed_slots WHERE class_id = ? AND subject_id = ? AND day_no = ? AND period_no = ? AND fixed_id <> ?", 'iiiii', array($cls, $sub, $day, $per, $ignore));
	if($x['n']) { return 'That fixed slot already exists'; }
	return '';
}

if($action == 'fetch') {
	$out = array('data' => array());
	$sql = "SELECT f.*, c.class_name, s.subject_name FROM fixed_slots f
		INNER JOIN subjects s ON s.subject_id = f.subject_id
		LEFT JOIN classes c ON c.class_id = f.class_id
		ORDER BY f.day_no, f.period_no, c.class_name";
	foreach(db_all($connect, $sql) as $r) {
		$out['data'][] = array($r['class_id'] ? h($r['class_name']) : '<em>Every class that has it</em>', h($r['subject_name']),
			h(isset($days[$r['day_no'] - 1]) ? $days[$r['day_no'] - 1] : 'Day ' . $r['day_no']), 'Period ' . (int)$r['period_no'], ui_row_buttons($r['fixed_id']));
	}
	json_out($out);
}

if($action == 'get') {
	$row = db_one($connect, "SELECT * FROM fixed_slots WHERE fixed_id = ?", 'i', array((int)pv('id')));
	json_out($row ? $row : array('success' => false, 'messages' => 'Fixed slot not found'));
}

if($action == 'create' || $action == 'edit') {
	$cls = (int)pv('class_id'); $sub = (int)pv('subject_id'); $day = (int)pv('day_no'); $per = (int)pv('period_no');
	$id = $action == 'edit' ? (int)pv('id') : 0;
	$err = fixed_check($connect, $cls, $sub, $day, $per, $id);
	if($err !== '') { json_out(array('success' => false, 'messages' => $err)); }
	if($action == 'create') {
		$ok = db_run($connect, "INSERT INTO fixed_slots (class_id, subject_id, day_no, period_no) VALUES (?,?,?,?)", 'iiii', array($cls, $sub, $day, $per));
		$msg = 'Successfully Added';
	} else {
		$ok = db_run($connect, "UPDATE fixed_slots SET class_id = ?, subject_id = ?, day_no = ?, period_no = ? WHERE fixed_id = ?", 'iiiii', array($cls, $sub, $day, $per, $id));
		$msg = 'Successfully Updated';
	}
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg : 'Error while saving the fixed slot'));
}

if($action == 'remove') {
	$ok = db_run($connect, "DELETE FROM fixed_slots WHERE fixed_id = ?", 'i', array((int)pv('id')));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed' : 'Error while removing the fixed slot'));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
