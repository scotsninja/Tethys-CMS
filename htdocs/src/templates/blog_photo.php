<?php
/* Copyright 20xx Productions */
extract($params);

$perPanel = 4;
$numPanels = ceil($totalPhotos / $perPanel);
?>
<div class="twelvecol last">
	<div id="blog-header">
		<div class="imgWrapper" style="float:left;margin-right:15px;max-height:80px;max-width:150px;"><a href="<?php echo $baseUrl; ?>"><img src="<?php echo $icon; ?>" alt="" /></a></div>
		<h1><?php echo $name; ?></h1>
	<?php if ($description != '') { ?>
		<h4><?php echo $description; ?></h4>
	<?php } ?>
	</div>
</div>
<div class="eightcol" style="margin-right:3%;">
<?php if (is_array($photos)) { ?>
	<div id="bpGallery">
		<div id="list">
		<?php if ($numPanels > 1) {  ?>
			<div id="pagination"></div><?php echo outputSteppedPagingLinks(); ?>
				<div id="previous" style="display:none;"><a href="javascript:void(0);" onclick="previousPanel();" class="">< Previous</a></div>
				<div id="next"><a href="javascript:void(0);" onclick="nextPanel();">1</a></div>
				<div id="next"><a href="javascript:void(0);" onclick="nextPanel();">2</a></div>
				<div id="next"><a href="javascript:void(0);" onclick="nextPanel();">3</a></div>
				<div id="next"><a href="javascript:void(0);" onclick="nextPanel();>Next ></a></div>
			</div>
		<?php } 
			
			for ($i = 0; $i < $numPanels; $i++) {
				$startIndex = $i*$perPanel;
				$display = ($i == 0) ? 'block' : 'none'; ?>
				<div id="galleryPanel<?php echo $i; ?>" class="galleryPanel" style="display:<?php echo $display; ?>;">
					<ul>
				<?php for ($j = $startIndex; $j < ($startIndex+$perPanel); $j++) { ?>
					<li style="width:50%;">
						<div class="photo <?php echo $photoClass; ?>"><div class="imgWrapper75"><a href="javascript:void(0);" onclick=""><img src="<?php echo $photos[$j]->imagePath; ?>" alt="<?php echo $photos[$j]->title; ?>" /></a></div></div>
					</li>
				<?php } ?>
					</ul>
				</div>
			<?php } ?>
		</div>
		<div id="main">main panel</div>
		<div id="details">image details</div>
	</div>
<?php } else { ?>
	<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">There are no active posts for this blog.</div>
<?php } ?>
</div>
<div class="fourcol last">
	<div class="box">
		<div id="blog-tagcloud">
			<div class="heading">Tags</div>
		<?php if (is_array($tags)) { ?>
			<div class="tagcloud">
			<?php foreach ($tags as $tag) { ?>
				<span class="<?php echo $tag['weight']; ?>"><a href="<?php echo $baseUrl; ?>?tag=<?php echo $tag['value']; ?>"><?php echo $tag['value']; ?></a></span>
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
</div>