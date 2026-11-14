<?php
/** Settings: save_general | save_periods | change_password | change_username */
require_once 'core.php';
$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'change_password') {
	$cur = pv('current') ; $new = pv('new'); $confirm = pv('confirm');
	$u = db_one($connect, "SELECT password FROM users WHERE user_id = ?", 'i', array($_SESSION['userId']));
	if(!$u || !password_verify($cur, $u['password'])) { json_out(array('success' => false, 'messages' => 'Current password is incorrect')); }
	if(strlen($new) < 4) { json_out(array('success' => false, 'messages' => 'New password must be at least 4 characters')); }
	if($new !== $confirm) { json_out(array('success' => false, 'messages' => 'The two new passwords do not match')); }
	$ok = db_run($connect, "UPDATE users SET password = ? WHERE user_id = ?", 'si', array(password_hash($new, PASSWORD_DEFAULT), $_SESSION['userId']));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Password changed' : 'Error while changing the password'));
}

if($action == 'change_username') {
	$name = trim(pv('username'));
	if($name === '') { json_out(array('success' => false, 'messages' => 'Username is required')); }
	$dup = db_one($connect, "SELECT COUNT(*) n FROM users WHERE username = ? AND user_id <> ?", 'si', array($name, $_SESSION['userId']));
	if($dup['n']) { json_out(array('success' => false, 'messages' => 'That username is taken')); }
	$ok = db_run($connect, "UPDATE users SET username = ? WHERE user_id = ?", 'si', array($name, $_SESSION['userId']));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Username changed' : 'Error while changing the username'));
}

require_admin_json();

if($action == 'save_general') {
	$name = trim(pv('school_name'));
	$valid = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
	$days = isset($_POST['days']) ? array_values(array_intersect($valid, $_POST['days'])) : array();
	if($name === '') { json_out(array('success' => false, 'messages' => 'School name is required')); }
	if(count($days) < 1) { json_out(array('success' => false, 'messages' => 'Choose at least one teaching day')); }
	set_setting($connect, 'school_name', $name);
	set_setting($connect, 'days', implode(',', $days));
	set_setting($connect, 'manual_hours', (string)max(0, (float)pv('manual_hours')));
	json_out(array('success' => true, 'messages' => 'Settings saved'));
}

if($action == 'save_periods') {
	$start = isset($_POST['start']) ? $_POST['start'] : array(); $end = isset($_POST['end']) ? $_POST['end'] : array(); $brk = isset($_POST['brk']) ? $_POST['brk'] : array();
	if(count($start) < 1) { json_out(array('success' => false, 'messages' => 'Add at least one period')); }
	$rows = array();
	foreach($start as $i => $s) {
		if(!preg_match('/^\d{1,2}:\d{2}$/', $s) || !preg_match('/^\d{1,2}:\d{2}$/', $end[$i])) { json_out(array('success' => false, 'messages' => 'Times must look like 08:00')); }
		$rows[] = array($i + 1, $s, $end[$i], isset($brk[$i]) && $brk[$i] ? 1 : 0);
	}
	$connect->query("DELETE FROM periods");
	foreach($rows as $r) { db_run($connect, "INSERT INTO periods (period_no, start_time, end_time, break_after) VALUES (?,?,?,?)", 'issi', $r); }
	json_out(array('success' => true, 'messages' => count($rows) . ' periods saved. Existing timetables keep their old period numbers - generate a new one.'));
}

json_out(array('success' => false, 'messages' => 'Unknown action'));
