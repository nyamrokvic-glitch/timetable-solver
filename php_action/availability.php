<?php
/** Teacher availability grid: get | save */
require_once 'core.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$tid = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;

if($action == 'get') {
	$slots = array();
	foreach(db_all($connect, "SELECT day_no, period_no, kind FROM teacher_slots WHERE teacher_id = ?", 'i', array($tid)) as $r) {
		$slots[$r['day_no'] . '-' . $r['period_no']] = $r['kind'];
	}
	json_out(array('success' => true, 'slots' => (object)$slots));
}

if($action == 'save') {
	$t = db_one($connect, "SELECT teacher_id FROM teachers WHERE teacher_id = ? AND teacher_status = 1", 'i', array($tid));
	if(!$t) { json_out(array('success' => false, 'messages' => 'Teacher not found')); }
	$slots = json_decode(isset($_POST['slots']) ? $_POST['slots'] : '{}', true);
	if(!is_array($slots)) { json_out(array('success' => false, 'messages' => 'Bad data')); }
	db_run($connect, "DELETE FROM teacher_slots WHERE teacher_id = ?", 'i', array($tid));
	$n = 0;
	foreach($slots as $key => $kind) {
		if(!preg_match('/^(\d+)-(\d+)$/', $key, $m) || ($kind !== 'avoid' && $kind !== 'unavailable')) { continue; }
		db_run($connect, "INSERT INTO teacher_slots (teacher_id, day_no, period_no, kind) VALUES (?,?,?,?)", 'iiis', array($tid, (int)$m[1], (int)$m[2], $kind));
		$n++;
	}
	json_out(array('success' => true, 'messages' => "Saved ($n restricted slot(s))"));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
