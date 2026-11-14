<?php

session_start();

require_once 'php_action/db_connect.php';
require_once 'includes/functions.php';

if(empty($_SESSION['userId'])) {
	header('location:' . $store_url . 'index.php');
	exit;
}

?>
