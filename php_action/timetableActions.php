<?php
/** Timetable actions: accept | unaccept | learn | unlearn | delete | rename | move */
require_once 'core.php';
require_once '../includes/validator.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tt = db_one($connect, "SELECT * FROM timetables WHERE timetable_id = ?", 'i', array($id));
if(!$tt) { json_out(array('success' => false, 'messages' => 'Timetable not found')); }

if($action == 'accept' || $action == 'unaccept' || $action == 'learn' || $action == 'unlearn') {
	$status = array('accept' => 'accepted', 'unaccept' => 'draft', 'learn' => 'historical', 'unlearn' => 'archived');
	$msg = array('accept' => 'Accepted. The solver will now learn from this timetable.', 'unaccept' => 'Moved back to draft. The solver no longer learns from it.',
		'learn' => 'The solver will learn from this timetable.', 'unlearn' => 'The solver will ignore this timetable.');
	if($action == 'accept') {
		$v = validate_timetable($connect, $id);
		if($v['stats']['hard'] > 0) { json_out(array('success' => false, 'messages' => 'It still has ' . $v['stats']['hard'] . ' clash(es) / missing lesson(s) - fix them before accepting.')); }
	}
	$ok = db_run($connect, "UPDATE timetables SET status = ? WHERE timetable_id = ?", 'si', array($status[$action], $id));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg[$action] : 'Could not update'));
}

if($action == 'delete') {
	$ok = db_run($connect, "DELETE FROM timetables WHERE timetable_id = ?", 'i', array($id));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Timetable deleted' : 'Could not delete'));
}

if($action == 'rename') {
	$name = trim(pv('name'));
	if($name === '') { json_out(array('success' => false, 'messages' => 'Name is required')); }
	$ok = db_run($connect, "UPDATE timetables SET name = ?, term = ? WHERE timetable_id = ?", 'ssi', array($name, trim(pv('term')), $id));
	json_out(array('success' => (bool)$ok, 'messages' => 'Saved'));
}

/**
 * Move one lesson to another slot. If that slot holds another lesson of the same
 * class the two are swapped. The independent validator checks the result: a move
 * that creates a NEW clash is rolled back unless force=1.
 */
if($action == 'move') {
	$lessonId = (int)pv('lesson_id'); $day = (int)pv('day'); $period = (int)pv('period');
	$force = !empty($_POST['force']);
	$P = load_problem($connect);
	if($day < 1 || $day > $P['D'] || $period < 1 || $period > $P['P']) { json_out(array('success' => false, 'messages' => 'That slot does not exist')); }
	$l = db_one($connect, "SELECT * FROM timetable_lessons WHERE lesson_id = ? AND timetable_id = ?", 'ii', array($lessonId, $id));
	if(!$l) { json_out(array('success' => false, 'messages' => 'Lesson not found')); }
	if($l['day_no'] == $day && $l['period_no'] == $period) { json_out(array('success' => false, 'messages' => 'The lesson is already there')); }

	$before = validate_timetable($connect, $id, $P);
	$beforeKeys = array_map(function($h) { return $h[0] . '|' . $h[1]; }, $before['hard']);

	$connect->begin_transaction();
	$other = db_one($connect, "SELECT * FROM timetable_lessons WHERE timetable_id = ? AND class_id = ? AND day_no = ? AND period_no = ? AND lesson_id <> ?", 'iiiii', array($id, $l['class_id'], $day, $period, $lessonId));
	if($other) {
		db_run($connect, "UPDATE timetable_lessons SET day_no = ?, period_no = ? WHERE lesson_id = ?", 'iii', array($l['day_no'], $l['period_no'], $other['lesson_id']));
	}
	db_run($connect, "UPDATE timetable_lessons SET day_no = ?, period_no = ? WHERE lesson_id = ?", 'iii', array($day, $period, $lessonId));

	$after = validate_timetable($connect, $id, $P);
	$new = array();
	foreach($after['hard'] as $h) { if(!in_array($h[0] . '|' . $h[1], $beforeKeys)) { $new[] = $h[1]; } }
	if($new && !$force) {
		$connect->rollback();
		json_out(array('success' => false, 'can_force' => true, 'messages' => 'That move would create a clash', 'conflicts' => $new));
	}
	$connect->commit();
	db_run($connect, "UPDATE timetables SET source = 'manual' WHERE timetable_id = ? AND source = 'generated'", 'i', array($id));
	json_out(array('success' => true, 'messages' => ($other ? 'Swapped with the lesson that was there. ' : 'Moved. ') . ($new ? 'Warning: ' . count($new) . ' clash(es) were created (forced).' : 'No clashes.')));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
