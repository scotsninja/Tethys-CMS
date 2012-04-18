<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

$includes['meta']['robots'] = 'none';
$includes['meta']['js-store'] = false;

if ($_SERVER['HTTP_REFERER'] != '') {
	if (preg_match('~^'.CORE_DOMAIN.'~', $_SERVER['HTTP_REFERER'])) {
		$urlArr = explode('/', $_SERVER['HTTP_REFERER']);
		$redirect = $urlArr[3];
	}
} else {
	$redirect = 'homepage';
}

if (User::isLoggedIn()) {
	User::logout();
}

header('Location: '.$redirect);
exit();

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output(); ?>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>