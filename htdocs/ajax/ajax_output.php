<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

if ($_POST['requireLogin']) {
	$action = (User::isLoggedIn()) ? $_POST['action'] : null;
} else {
	$action = $_POST['action'];
}


$error = array('type' => MSG_ERROR, 'message' => 'Error: Unknown action');

if ($error) {
	echo '<div class="'.SystemMessage::getMessageClass($error['type']).'">'.$error['message'].'</div>';
} else {
	echo $ret;
}

?>