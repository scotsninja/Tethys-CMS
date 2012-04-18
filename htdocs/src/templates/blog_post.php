<?php
/* Copyright 20xx Productions */
extract($params);

$blogs = Blog::search();
?>
<div class="twelvecol last">
<?php if ($blogs && $GLOBALS['isMobile']) { ?>
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
	<?php if (is_array($posts)) { ?>
		<?php if (ceil($totalPosts/$perPage) > 1) { ?>
		<div class="blog-nav">
			<table>
				<tr>
					<td><?php echo ($pageNum > 1) ? '<a href="'.$baseUrl.'?page='.($pageNum-1).'" class="button button-blue">View More Recent</a>' : '&nbsp;'; ?></td>
					<td style="text-align:right;"><?php echo ($perPage > 0 && $pageNum < (ceil($totalPosts/$perPage))) ? '<a href="'.$baseUrl.'?page='.($pageNum+1).'" class="button button-blue">View Older</a>' : '&nbsp;'; ?></td>
				</tr>
			</table>
		</div>
		<?php } ?>
		<?php $i = 0;
		foreach ($posts as $post) { ?>
			<div class="post <?php echo ($i++%2==0) ? 'evenRow' : 'oddRow'; ?>">
			<?php if ($GLOBALS['dtObj']->comp('now', $post->datePosted) < 0) { ?>
				<h4 class="message message-notice" style="display:block;width:100%;text-align:left;"><a href="<?php echo $post->fullUrl; ?>"><?php echo $post->title; ?> (Not Active)</a></h4>
			<?php } else { ?>
				<h4><a href="<?php echo $post->fullUrl; ?>"><?php echo $post->title; ?></a></h4>
			<?php } ?>
				<div class="excerpt"><?php echo $post->getBlurb(); ?></div>
			<?php if ($post->tags != '') { ?>
				<div class="tags">
				<?php $totTags = count($post->tagArr);
				for($j = 0; $j < $totTags; $j++) { ?>
					<a href="<?php echo $baseUrl.'?tag='.rawurlencode($post->tagArr[$j]); ?>"><?php echo $post->tagArr[$j]; ?></a>
					<?php if ($j < ($totTags-1)) { echo ' | '; } ?>
				<?php } ?>
				</div>
			<?php } ?>
				<div class="comments"><?php echo outputDisqusCommentCount(CORE_DOMAIN.substr($post->fullUrl,1)); ?></div>
				<div class="date">Posted on <?php echo $GLOBALS['dtObj']->format($post->datePosted); ?></div>
			</div>
		<?php } ?>
		<?php if (ceil($totalPosts/$perPage) > 1) { ?>
		<div class="blog-nav">
			<table>
				<tr>
					<td><?php echo ($pageNum > 1) ? '<a href="'.$baseUrl.'?page='.($pageNum-1).'" class="button button-blue">View More Recent</a>' : '&nbsp;'; ?></td>
					<td style="text-align:right;"><?php echo ($perPage > 0 && $pageNum < (ceil($totalPosts/$perPage))) ? '<a href="'.$baseUrl.'?page='.($pageNum+1).'" class="button button-blue">View Older</a>' : '&nbsp;'; ?></td>
				</tr>
			</table>
		</div>
		<?php } ?>
	<?php } else { ?>
		<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">There are no active posts for this blog.</div>
	<?php } ?>
	</div>
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
<?php outputDisqusCommentCountScript(); ?>