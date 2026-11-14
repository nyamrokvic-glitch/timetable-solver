<?php
/** Rooms & labs: fetch | get | create | edit | remove */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'fetch') {
	$out = array('data' => array());
	foreach(db_all($connect, "SELECT * FROM rooms WHERE room_status = 1 ORDER BY room_type, room_name") as $r) {
		$out['data'][] = array(h($r['room_name']), h($r['room_type']), (int)$r['capacity'], ui_status_label($r['room_active']), ui_row_buttons($r['room_id']));
	}
	json_out($out);
}

if($action == 'get') {
	$row = db_one($connect, "SELECT * FROM rooms WHERE room_id = ? AND room_status = 1", 'i', array((int)pv('id')));
	json_out($row ? $row : array('success' => false, 'messages' => 'Room not found'));
}

if($action == 'create' || $action == 'edit') {
	$name = trim(pv('room_name'));
	$type = preg_replace('/[^a-z0-9_]+/', '_', strtolower(trim(pv('room_type'))));
	$cap = (int)pv('capacity'); $active = (int)pv('room_active');
	if($name === '') { json_out(array('success' => false, 'messages' => 'Room name is required')); }
	if($type === '') { json_out(array('success' => false, 'messages' => 'Room type is required (e.g. chem_lab, computer_lab, field)')); }
	if($cap < 1) { json_out(array('success' => false, 'messages' => 'Capacity must be at least 1')); }
	if($action == 'create') {
		$ok = db_run($connect, "INSERT INTO rooms (room_name, room_type, capacity, room_active, room_status) VALUES (?,?,?,?,1)", 'ssii', array($name, $type, $cap, $active));
		$msg = 'Successfully Added';
	} else {
		$ok = db_run($connect, "UPDATE rooms SET room_name = ?, room_type = ?, capacity = ?, room_active = ? WHERE room_id = ?", 'ssiii', array($name, $type, $cap, $active, (int)pv('id')));
		$msg = 'Successfully Updated';
	}
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? $msg : 'Error while saving the room'));
}

if($action == 'remove') {
	$ok = db_run($connect, "UPDATE rooms SET room_status = 2 WHERE room_id = ?", 'i', array((int)pv('id')));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed' : 'Error while removing the room'));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
