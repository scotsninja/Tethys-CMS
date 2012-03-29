<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

/* meta tags */
$metaTitle = 'Server Error';
$metaDescription = 'Internal server error';
$metaRobots = 'none';

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
	<div class="box">Uh-oh!  Something went terribly wrong.  Not to worry, though; it's not your fault.  Why don't you try again?</div>
</div>
<div id="rightCol" class="sixcol last">
	<div class="box"><img src="http://20xxproductions.com/files/error_500.jpg" alt="500 Error" /></div>
</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>