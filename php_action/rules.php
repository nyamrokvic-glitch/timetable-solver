<?php
/** Rule weights: save */
require_once 'core.php';
require_admin_json();

if(isset($_GET['action']) && $_GET['action'] == 'save') {
	$w = isset($_POST['weights']) && is_array($_POST['weights']) ? $_POST['weights'] : array();
	$n = 0;
	foreach($w as $key => $val) {
		$val = max(0, min(20, (int)$val));
		if(db_run($connect, "UPDATE rules SET weight = ? WHERE rule_key = ? AND kind = 'soft'", 'is', array($val, $key))) { $n++; }
	}
	json_out(array('success' => true, 'messages' => 'Rule weights saved'));
}
json_out(array('success' => false, 'messages' => 'Unknown action'));
