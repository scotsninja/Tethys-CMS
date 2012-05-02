<?php /* Copyright 20xx Productions */
$mobDetect = new Mobile_Detect();
$isMobile = $mobDetect->isHandheld();

$includeDefaults['jquery'] = true;
$includeDefaults['jquery-ui'] = false;
$includeDefaults['qtip'] = false;

$includeParams = (is_array($includeParams)) ? array_merge($includeDefaults, $includeParams) : $includeDefaults; ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<title><?php echo ($includes['meta']['title'] != '') ? $includes['meta']['title'] : SITE_TITLE; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="<?php echo ($includes['meta']['description'] != '') ? $includes['meta']['description'] : SITE_DESCRIPTION; ?>" />
<meta name="keywords" content="<?php echo ($includes['meta']['keywords'] != '') ? $includes['meta']['keywords'] : SITE_KEYWORDS; ?>" />
<meta name="author" content="<?php echo ($includes['meta']['author'] != '') ? $includes['meta']['author'] : SITE_AUTHOR; ?>" />
<meta name="robots" content="<?php echo ($includes['meta']['robots'] != '') ? $includes['meta']['robots'] : 'all'; ?>" />
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<meta name="copyright" content="<?php echo date('Y'); ?> <?php echo SITE_TITLE; ?>" />
<meta name="generator" content="TethysCMS <?php echo CORE_VERSION; ?>" />

<?php if (isset($includes['headers'])) {
echo $includes['headers'];
} ?>

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" media="all" href="/<?php echo CORE_CSS_DIR; ?>reset.css" />

<?php /* Minify/Compress CSS Files */
$cssFiles = array();
if ($includeParams['jquery-ui']) {
	$cssFiles[] = 'jquery-themes/overcast/jquery-ui-1.8.17.custom.css';
}
if ($includeParams['qtip']) {
	$cssFiles[] = 'jquery.qtip.min.css';
}
$cssFiles[] = '1140.css';
$cssFiles[] = 'tethys.css';
if ($isMobile) {
	$cssFiles[] = 'mobile.css';
}

if (file_exists(CORE_DIR_DEPTH.CORE_CSS_DIR.'local.css')) {
	$cssFiles[] = 'local.css';
}
?>

<link rel="stylesheet" type="text/css" media="all" href="/min/?b=<?php echo substr(CORE_CSS_DIR, 0, strlen(CORE_CSS_DIR)-1); ?>&amp;f=<?php echo implode(',', $cssFiles); ?>" />

<!-- 1140px Grid styles for IE -->
<!--[if lte IE 9]><link rel="stylesheet" href="/<?php echo CORE_CSS_DIR; ?>/ie.css" type="text/css" media="screen" /><![endif]-->

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