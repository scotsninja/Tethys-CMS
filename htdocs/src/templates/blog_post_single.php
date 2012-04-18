<?php
/* Copyright 20xx Productions */
extract($params);

$blogs = Blog::search();
?>
<div class="twelvecol last">
	<div id="blog-header">
		<div class="imgWrapper imgWrapper150" style="float:left;margin-right:15px;"><a href="<?php echo $baseUrl; ?>"><img style="max-height:70px;" src="<?php echo $icon; ?>" alt="" /></a></div>
		<h1><a href="<?php echo $baseUrl; ?>"><?php echo $name; ?></a></h1>
	<?php if ($description != '') { ?>
		<h4><?php echo $description; ?></h4>
	<?php } ?>
	</div>
</div>
<div class="eightcol" style="margin-right:3%;">
	<div class="box">
		<div class="post">
		<?php if ($GLOBALS['dtObj']->comp('now', $postDate) < 0) { ?>
			<h4 class="message message-notice" style="display:block;width:100%;text-align:left;"><?php echo $postTitle; ?> (Not Active)</h4>
		<?php } else { ?>
			<h4><?php echo $postTitle; ?></h4>
		<?php } ?>
			<div class="date">Posted on <?php echo $GLOBALS['dtObj']->format($postDate); ?></div>
			<div class="excerpt"><?php echo $postValue; ?></div>
		<?php if ($postTags != '') { ?>
			<div class="tags">
			<?php $tagArr = explode(',', $postTags);
			$totTags = count($tagArr);
			for($i = 0; $i < $totTags; $i++) { ?>
				<a href="<?php echo $baseUrl.'?tag='.rawurlencode($tagArr[$i]); ?>"><?php echo $tagArr[$i]; ?></a>
				<?php if ($i < ($totTags-1)) { echo ' | '; } ?>
			<?php } ?>
			</div>
		<?php } ?>
			<div><?php echo outputSharingLinks(array('share','facebook','twitter','google','digg','reddit','email')); ?></div>
		</div>
	</div>
	<?php if ($postComments == 'open') { ?>
		<div class="box"><?php echo outputDisqus(); ?></div>
	<?php } ?>
	<div id="blog-footer"></div>
</div>
<div class="fourcol last">
<?php if ($blogs && !$GLOBALS['isMobile']) { ?>
	<div class="box">
		<div id="blog-list">
			<div class="heading">Blogs</div>
			<ul>
			<?php foreach ($blogs as $b) { ?>
				<li><a href="<?php echo $b->fullUrl; ?>"><?php echo $b->name; ?></a> - <?php echo $b->description; ?></li>
			<?php } ?>
			</ul>
		</div>
	</div>
<?php } ?>
	<div class="box">
		<div id="blog-search">
			<div class="heading">Search</div>
			<div><form method="GET" action="<?php echo $baseUrl; ?>">
				<input type="text" name="search" id="search" value="<?php echo $_GET['search']; ?>" size="35" />
				<button type="submit" value="Search">Search</button><br />
			</form></div>
		</div>
	</div>
	<div class="box">
		<div id="blog-tagcloud">
			<div class="heading">Tags</div>
		<?php if (is_array($tags)) { ?>
			<div class="tagcloud">
			<?php foreach ($tags as $tag) { ?>
				<span class="<?php echo $tag['weight']; ?>"><a href="<?php echo $baseUrl; ?>?tag=<?php echo rawurlencode($tag['value']); ?>"><?php echo $tag['value']; ?></a></span>
			<?php } ?>
			</div>
		<?php } ?>
		</div>
	</div>
	<div class="box">
		<div id="blog-archives">
			<div class="heading">Archives</div>
		<?php if (is_array($archives)) { ?>
			<ul>
			<?php foreach ($archives as $a) { ?>
				<li><a href="<?php echo $baseUrl; ?>?month=<?php echo $a['month']; ?>&year=<?php echo $a['year']; ?>"><?php echo $a['label']; ?> (<?php echo $a['total']; ?>)</a></li>
			<?php } ?>
			</ul>
		<?php } ?>
		</div>
	</div>
	<?php if ($rss && file_exists($rss)) { ?>
		<div id="feed"><a href="/<?php echo $rss; ?>"><img src="/<?php echo CORE_DIR_DEPTH.CORE_ICON_PATH.'rss_32.png'; ?>" alt="RSS Feed" / > Subscribe</a></div>
	<?php } ?>
</div>