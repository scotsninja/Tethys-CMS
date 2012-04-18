<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

$pageTitle = 'Page Stats';

// dynamic pages
$dPages = array(
	array('name' => 'Homepage', 'url' => 'homepage', 'description' => '', 'file' => 'index.php', 'views' => Page::getNumViews('homepage', 'index.php'), 'lastViewed' => Page::getLastViewed('homepage', 'index.php'), 'avgLoad' => Page::getAverageLoadTime('homepage', 'index.php')),
	array('name' => 'Portfolio', 'url' => 'portfolio', 'description' => '', 'file' => 'portfolio.php', 'views' => Page::getNumViews('portfolio', 'portfolio.php'), 'lastViewed' => Page::getLastViewed('portfolio', 'portfolio.php'), 'avgLoad' => Page::getAverageLoadTime('portfolio', 'portfolio.php')),
	array('name' => 'Contact Form', 'url' => 'contact', 'description' => '', 'file' => 'contact.php', 'views' => Page::getNumViews('contact', 'contact.php'), 'lastViewed' => Page::getLastViewed('contact', 'contact.php'), 'avgLoad' => Page::getAverageLoadTime('contact', 'contact.php')),
	array('name' => 'Login Form', 'url' => 'login.php', 'description' => '', 'file' => 'login.php', 'views' => Page::getNumViews('login.php'), 'lastViewed' => Page::getLastViewed('login.php'), 'avgLoad' => Page::getAverageLoadTime('login.php'))
);

// static pages
$pages = Page::search($search, 1000, 1, $totalResults);

foreach ($pages as $p) {
	$dPages[] = array('name' => $p->name, 'url' => $p->url, 'description' => '', 'file' => '', 'views' => Page::getNumViews($p->url), 'lastViewed' => Page::getLastViewed($p->url), 'avgLoad' => Page::getAverageLoadTime($p->url));
}

foreach ($dPages as $key => $row) {
	$name[$key] = $row['name'];
}

array_multisort($name, SORT_ASC, $dPages);

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php'); ?>

<h1><?php echo $pageTitle; ?></h1>
<?php SystemMessage::output(); ?>

<div class="box">
<?php if ($dPages) { ?>
	<table class="tableResults">
		<tr>
			<th>Name</th>
			<th>URL</th>
			<th>Description</th>
			<th width="1%" nowrap="nowrap"># Views</th>
			<th width="1%" nowrap="nowrap">Last View</th>
			<th width="1%" nowrap="nowrap">Avg Load</th>
		</tr>
	<?php $i = 0;
	foreach ($dPages as $p) {
		$class = ($i++%2==1) ? 'evenRow' : 'oddRow'; ?>
		<tr class="<?php echo $class; ?>">
			<td><?php echo $p['name']; ?></td>
			<td><a href="/<?php echo $p['url']; ?>" target="_blank"><?php echo $p['url']; ?></a>&nbsp;</td>
			<td><?php echo $p['description']; ?></td>
			<td><?php echo $p['views']; ?></td>
			<td nowrap="nowrap"><?php if ($p['lastViewed']) {
				echo $GLOBALS['dtObj']->format($p['lastViwed']);
			} else {
				echo '&nbsp;';
			} ?></td>
			<td nowrap="nowrap"><?php echo (CORE_BENCHMARK_LEVEL > 0 && $p['avgLoad'] > 0) ? number_format($p['avgLoad'], 3).' sec' : 'n/a'; ?></td>
		</tr>
	<?php } ?>
	</table>
<?php } else { ?>
	<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">No results returned.</div>
<?php } ?>
</div>

<?php include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php'); ?>