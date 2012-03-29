<?php /* Copyright 20xx Productions */
$mobDetect = new Mobile_Detect();
$isMobile = $mobDetect->isHandheld();

$includeDefaults['jquery'] = (CORE_BENCHMARK_LEVEL > 1) ? true : false;
$includeDefaults['jquery-ui'] = false;
$includeDefaults['qtip'] = false;

$includeParams = (is_array($includeParams)) ? array_merge($includeDefaults, $includeParams) : $includeDefaults; ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<title><?php echo ($metaTitle != '') ? $metaTitle : SITE_TITLE; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="<?php echo ($metaDescription != '') ? $metaDescription : SITE_DESCRIPTION; ?>" />
<meta name="keywords" content="<?php echo ($metaKeywords != '') ? $metaKeywords : SITE_KEYWORDS; ?>" />
<meta name="author" content="<?php echo ($metaAuthor != '') ? $metaAuthor : SITE_AUTHOR; ?>" />
<meta name="robots" content="<?php echo ($metaRobots != '') ? $metaRobots : 'all'; ?>" />
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<meta name="copyright" content="&copy; <?php echo date('Y'); ?> <?php echo SITE_TITLE; ?>" />
<meta name="generator" content="TethysCMS <?php echo CORE_VERSION; ?>" />

<?php if (isset($includes['headers'])) {
echo $includes['headers'];
} ?>

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" media="all" href="/<?php echo CORE_CSS_DIR; ?>reset.css" />
<!-- 1140px Grid styles for IE -->
<!--[if lte IE 9]><link rel="stylesheet" href="/<?php echo CORE_CSS_DIR; ?>/ie.css" type="text/css" media="screen" /><![endif]-->
<!-- The 1140px Grid - http://cssgrid.net/ -->
<?php if ($includeParams['jquery-ui']) { ?>
<link rel="stylesheet" href="/<?php echo CORE_CSS_DIR; ?>jquery-themes/overcast/jquery-ui-1.8.17.custom.css">
<?php } ?>
<?php if ($includeParams['qtip']) { ?>
<link rel="stylesheet" type="text/css" media="all" href="/<?php echo CORE_CSS_DIR; ?>jquery.qtip.min.css" />
<?php } ?>
<link rel="stylesheet" type="text/css" media="all" href="/<?php echo CORE_CSS_DIR; ?>global.css" />
<?php if ($isMobile) { ?>
<link media="only screen and (max-device-width: 480px)" href="/<?php echo CORE_CSS_DIR; ?>mobile.css" type="text/css" rel="stylesheet" />
<?php } ?>

<?php if (isset($includes['css'])) {
echo $includes['css'];
} ?>

</head>
<body>
<header>
<div class="container" id="headerBg"></div>
</header>
<div class="container">
	<div class="row" id="mainContentArea">
		<!-- Main Column -->
		<div class="twelvecol last">