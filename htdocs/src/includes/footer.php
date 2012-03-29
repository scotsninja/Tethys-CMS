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
	<?php } ?>
	<?php if ($includeParams['qtip']) { ?>
	<script type="text/javascript" src="/<?php echo CORE_JS_DIR; ?>jquery.qtip.min.js"></script>
	<?php } ?>
<?php } ?>
<script type="text/javascript" src="/<?php echo CORE_JS_DIR; ?>global.js"></script>
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
<?php if ($includeParams['jquery'] || $includeParams['jquery-ui'] || $includeParams['qtip']) { ?>
	$(document).ready(function() {
		init();
	});
<?php } ?>
</script>
</body>
</html>
<?php recordPageView(); ?>
<?php SystemMessage::clear(); ?>
<?php ob_end_flush(); ?>
<?php $GLOBALS['bmObj']->log(1, 'Script end', 'This is the end of the script', true); ?>