<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

/* meta tags */
$metaTitle = 'Page Not Found';
$metaDescription = 'The requested resource could not be found but may be available again in the future.';
$metaRobots = 'none';

$headers['css'] = '<style type="text/css">
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
	<div class="box">Oops!  We seem to have misplaced your page.  Why don't you check out one of these other, awesome links:
		<ul>
			<li><a href="/blogs">Blog</a></li>
			<li><a href="/portfolio">Portfolio</a></li>
			<li><a href="/projects">Projects</a></li>
		</ul>
	</div>
</div>
<div id="rightCol" class="sixcol last">
	<div class="box"><img src="http://20xxproductions.com/files/error_404.jpg" alt="404 Error" /></div>
</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>