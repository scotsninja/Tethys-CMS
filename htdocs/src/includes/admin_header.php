<?php /* Copyright 20xx Productions */ ?>
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
<!-- 1140px Grid styles for IE -->
<!--[if lte IE 9]><link rel="stylesheet" href="/<?php echo CORE_CSS_DIR; ?>/ie.css" type="text/css" media="screen" /><![endif]-->
<?php /* Minify/Compress CSS Files */
$cssFiles = array();
$cssFiles[] = 'jquery-themes/overcast/jquery-ui-1.8.17.custom.css';
$cssFiles[] = 'jquery.qtip.min.css';
$cssFiles[] = '1140.css';
$cssFiles[] = 'tethys.css';
$cssFiles[] = 'admin.css';

if (file_exists(CORE_DIR_DEPTH.CORE_CSS_DIR.'local.css')) {
	$cssFiles[] = 'local.css';
}
?>

<link rel="stylesheet" type="text/css" media="all" href="/min/?b=<?php echo substr(CORE_CSS_DIR, 0, strlen(CORE_CSS_DIR)-1); ?>&amp;f=<?php echo implode(',', $cssFiles); ?>" />

<?php if (isset($includes['css'])) {
echo $includes['css'];
} ?>

</head>

<body>
<header>
<div id="headerBg"></div>
</header>
<div class="container">
	<div class="row" id="mainContentArea">
		<!-- Admin Nav -->
		<div class="twocol"><div id="admin-nav">
			<h2>Admin Panel</h2>
			<nav>
			<ul>
				<li><a href="/">Site Home</a></li>
				<li<?php echo ($_SERVER['PHP_SELF'] == '/admin/admin_pages.php') ? ' class="selected"' : ''; ?>><a href="admin_pages.php?v=list">Pages</a></li>
				<li<?php echo ($_SERVER['PHP_SELF'] == '/admin/admin_blogs.php') ? ' class="selected"' : ''; ?>><a href="admin_blogs.php?v=list">Blogs</a></li>
				<li<?php echo ($_SERVER['PHP_SELF'] == '/admin/admin_users.php') ? ' class="selected"' : ''; ?>><a href="admin_users.php?v=list">Users</a></li>
				<li<?php echo ($_SERVER['PHP_SELF'] == '/admin/admin_banned.php') ? ' class="selected"' : ''; ?>><a href="admin_banned.php?v=user">Ban List</a></li>
				<li<?php echo ($_SERVER['PHP_SELF'] == '/admin/admin_stats.php') ? ' class="selected"' : ''; ?>><a href="admin_stats.php">Stats</a></li>
				<li<?php echo ($_SERVER['PHP_SELF'] == '/admin/admin_config.php') ? ' class="selected"' : ''; ?>><a href="admin_config.php">Config</a></li>
			</ul>
			</nav>
		</div></div>
		<!-- Main Column -->
		<div class="tencol last">