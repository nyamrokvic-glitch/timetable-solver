<?php
/** Users: fetch | create | remove */
require_once 'core.php';
require_once '../includes/ui.php';
require_admin_json();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'fetch') {
	$out = array('data' => array());
	foreach(db_all($connect, "SELECT user_id, username, email, role FROM users ORDER BY user_id") as $r) {
		$btn = $r['user_id'] == $_SESSION['userId'] ? '<span class="text-slate-400">(you)</span>' :
			'<a type="button" class="btn btn-danger btn-sm" onclick="crud.remove(' . (int)$r['user_id'] . ')"><i class="glyphicon glyphicon-trash"></i> Remove</a>';
		$out['data'][] = array(h($r['username']), h($r['email']), $r['role'] == 'admin' ? "<label class='label label-info'>Administrator</label>" : "<label class='label label-default'>Staff (view only)</label>", $btn);
	}
	json_out($out);
}

if($action == 'create') {
	$u = trim(pv('username')); $p = pv('password'); $role = pv('role') == 'admin' ? 'admin' : 'staff';
	if($u === '' || strlen($p) < 4) { json_out(array('success' => false, 'messages' => 'Username is required and the password needs 4+ characters')); }
	$dup = db_one($connect, "SELECT COUNT(*) n FROM users WHERE username = ?", 's', array($u));
	if($dup['n']) { json_out(array('success' => false, 'messages' => 'That username already exists')); }
	$ok = db_run($connect, "INSERT INTO users (username, password, email, role) VALUES (?,?,?,?)", 'ssss', array($u, password_hash($p, PASSWORD_DEFAULT), trim(pv('email')), $role));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Added' : 'Error while adding the user'));
}

if($action == 'remove') {
	$id = (int)pv('id');
	if($id == $_SESSION['userId'] || $id == 1) { json_out(array('success' => false, 'messages' => 'You cannot remove this user')); }
	$ok = db_run($connect, "DELETE FROM users WHERE user_id = ?", 'i', array($id));
	json_out(array('success' => (bool)$ok, 'messages' => $ok ? 'Successfully Removed' : 'Error while removing the user'));
}
json_out(array('success' => false, 'messages' => 'Unknown action'));
