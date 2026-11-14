<?php

session_start();

require_once 'db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// every php_action endpoint requires a signed-in user
if(empty($_SESSION['userId'])) {
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		http_response_code(401);
		echo json_encode(array('success' => false, 'messages' => 'Session expired - please sign in again'));
	} else {
		header('location:../' . $store_url);
	}
	exit;
}

?>
