<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

if ($_POST['requireLogin']) {
	$action = (User::isLoggedIn()) ? $_POST['action'] : null;
} else {
	$action = $_POST['action'];
}

// 
if ($action == 'log-pageload') {
	$benchMark = new Benchmark(CORE_BENCHMARK_LEVEL, $_POST['start'], $_POST['pageId'], $_POST['page'], $_POST['vars']);
	$benchMark->log(2, 'jQuery Ready', 'jQuery ready functions fired', true);
} else if ($action == 'log-pagerender') {
	$benchMark = new Benchmark(CORE_BENCHMARK_LEVEL, $_POST['start'], $_POST['pageId'], $_POST['page'], $_POST['vars']);
	$benchMark->log(2, 'Page Render', 'The page has loaded and the onload event has fired', true);
} else {
	$ret = array('error' => 'Unknown action.');
}

echo json_encode($ret);

?>