<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

header("HTTP/1.0 403 Forbidden");

/* meta tags */
$includes['meta']['title'] = 'Forbidden';
$includes['meta']['description'] = 'The request was a legal request, but the server is refusing to respond to it.';
$includes['meta']['robots'] = 'none';
$includes['meta']['js-store'] = false;

$includes['css'] = '<style type="text/css">
	#leftCol {
		font-size:1.2em;
	}
	
	#leftCol ul {
		list-style: none;
		margin: 6px 20px;
	}
</style>';

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output();

?>

<div id="leftCol" class="sixcol">
	<div class="box">Uh-oh!  I don't think you were supposed to do that.  Why don't you try something else?</div>
</div>
<div id="rightCol" class="sixcol last">
	<div class="box"><img src="http://20xxproductions.com/files/error_403.jpg" alt="403 Error" /></div>
</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>