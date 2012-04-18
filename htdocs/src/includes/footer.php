<?php /* Copyright 20xx Productions */ ?>
</div> <!-- end contentWrapper -->
</div> <!-- end row -->
</div> <!-- end container -->
<!-- footer -->
<footer>
	<div class="twelvecol last">&copy;<?php echo date('Y'); ?> <?php echo SITE_TITLE; ?></div>
</footer>
<div id="mask"></div>
<?php if ($includeParams['jquery'] || $includeParams['jquery-ui'] || $includeParams['qtip']) { ?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<?php if ($includeParams['jquery-ui']) { ?>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
	<?php }
}
/* Minify/Compress JS files */
$jsFiles = array();

if ($includeParams['qtip']) {
	$jsFiles[] = 'jquery.qtip.min.js';
}

$jsFiles[] = 'tethys.js';

if (file_exists(CORE_DIR_DEPTH.CORE_JS_DIR.'local.js')) {
	$jsFiles[] = 'local.js';
}
?>
<script type="text/javascript" src="/min/?b=<?php echo substr(CORE_JS_DIR, 0, strlen(CORE_JS_DIR)-1); ?>&amp;f=<?php echo implode(',',$jsFiles); ?>"></script>

<?php if (isset($includes['js'])) {
echo $includes['js'];
} ?>
<script type="text/javascript">
	var bmLevel = <?php echo CORE_BENCHMARK_LEVEL; ?>;
	var bmStartTime = '<?php echo $GLOBALS['bmObj']->startTime; ?>';
	var bmPageId = '<?php echo $GLOBALS['bmObj']->pageId; ?>';
	var bmPage = '<?php echo $GLOBALS['bmObj']->page; ?>';
	var bmVars = '<?php echo $GLOBALS['bmObj']->vars; ?>';
	var gTooltips = <?php echo ($includeParams['qtip']) ? 'true' : 'false'; ?>;
	var storePage = <?php echo ($includes['meta']['js-store'] === false) ? 'false' : 'true'; ?>;
<?php if ($includeParams['jquery'] || $includeParams['jquery-ui'] || $includeParams['qtip']) { ?>
	$(document).ready(function() {
		init();
	});
<?php } else { ?>
	window.onload = init;
<?php } ?>
</script>
<!-- place analytics code here -->
</body>
</html>
<?php recordPageView(); ?>
<?php SystemMessage::clear(); ?>
<?php ob_end_flush(); ?>
<?php $GLOBALS['bmObj']->log(1, 'Script end', 'This is the end of the script', true); ?>