<?php
/* Copyright 20xx Productions */
extract($params);

$perPanel = 8;
$numPanels = ceil($totalImages / $perPanel);
?>
<div class="<?php echo ($GLOBALS['isMobile']) ? 'twelvecol last' : 'eightcol'; ?>">
	<div class="box">
		<div class="heading"><?php echo $name; ?></div>
	<?php if ($url != '') { ?>
		<div class="line"><a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a></div>
	<?php } ?>
	<?php if ($purpose != '') { ?>
		<div class="line"><span class="lineHeading">Purpose:</span> <?php echo $purpose; ?></div>
	<?php } ?>
	<?php if ($status != '') { ?>
		<div class="line"><span class="lineHeading">Status:</span> <?php echo $status; ?> <?php echo Tooltip::outputInfo(Project::getStatusTooltip($status)); ?></div>
	<?php } ?>
	<?php if ($platform != '') { ?>
		<div class="line"><span class="lineHeading">Platform:</span> <?php echo $platform; ?></div>
	<?php } ?>
	<?php if ($details != '') { ?>
		<hr />
		<div><?php echo $details; ?></div>
	<?php } ?>
	</div>
<?php if ($GLOBALS['isMobile']) { ?>
	<div class="box" style="height:500px;">
		<div class="heading">Gallery</div>
	<?php if (is_array($images)) {
		if ($numPanels > 1) { ?>
			<div id="galleryNav">
				<div id="previous" style="display:none;"><a href="javascript:void(0);" onclick="previousPanel();" class="button button-blue">Previous</a></div>
				<div id="next"><a href="javascript:void(0);" onclick="nextPanel();" class="button button-blue">Next</a></div>
			</div>
		<?php }
		
		for ($i = 0; $i < $numPanels; $i++) {
			$startIndex = $i*$perPanel;
			$display = ($i == 0) ? 'block' : 'none'; ?>
			<div id="galleryPanel<?php echo $i; ?>" class="galleryPanel" style="display:<?php echo $display; ?>;">
				<ul>
			<?php for ($j = $startIndex; $j < ($startIndex+$perPanel); $j++) {
				if ($images[$j]) { ?>
					<li<?php echo ($j%2==0) ? ' style="clear:left;"' : ''; ?>>
						<div class="item">
							<div class="imgWrapper imgWrapper75"><a class="fbox-img" href="<?php echo $images[$j]['path']; ?>" rel="gallery<?php echo $i; ?>" title="<?php echo $images[$j]['caption']; ?>"><img src="<?php echo $images[$j]['path']; ?>" alt="<?php echo $images[$j]['title']; ?>" /></a></div>
						<?php if ($images[$j]['title'] != '') { ?>
							<div class="title"><?php echo $images[$j]['title']; ?></div>
						<?php } ?>
						</div>
					</li>
				<?php } ?>
			<?php } ?>
				</ul>
			</div>
		<?php } ?>
	<?php } else { ?>
		<div><div class="imgWrapper imgWrapper150"><img src="/img/skin/no_img_available.png" alt="No Image Available" /></div></div>
	<?php } ?>
	</div>
<?php } ?>
<?php if ($comments) { ?>
	<div class="box">
		<div class="heading">Comments</div>
		<div><?php echo outputDisqus(); ?></div>
	</div>
<?php } ?>
</div>
<?php if (!$GLOBALS['isMobile']) { ?>
<div class="fourcol last">
	<div class="box" style="height:500px;">
		<div class="heading">Gallery</div>
	<?php if (is_array($images)) {
		if ($numPanels > 1) { ?>
			<div id="galleryNav">
				<div id="previous" style="display:none;"><a href="javascript:void(0);" onclick="previousPanel();" class="button button-blue">Previous</a></div>
				<div id="next"><a href="javascript:void(0);" onclick="nextPanel();" class="button button-blue">Next</a></div>
			</div>
		<?php }
		
		for ($i = 0; $i < $numPanels; $i++) {
			$startIndex = $i*$perPanel;
			$display = ($i == 0) ? 'block' : 'none'; ?>
			<div id="galleryPanel<?php echo $i; ?>" class="galleryPanel" style="display:<?php echo $display; ?>;">
				<ul>
			<?php for ($j = $startIndex; $j < ($startIndex+$perPanel); $j++) {
				if ($images[$j]) { ?>
					<li<?php echo ($j%2==0) ? ' style="clear:left;"' : ''; ?>>
						<div class="item">
							<div class="imgWrapper imgWrapper75"><a class="fbox-img" href="<?php echo $images[$j]['path']; ?>" rel="gallery<?php echo $i; ?>" title="<?php echo $images[$j]['caption']; ?>"><img src="<?php echo $images[$j]['path']; ?>" alt="<?php echo $images[$j]['title']; ?>" /></a></div>
						<?php if ($images[$j]['title'] != '') { ?>
							<div class="title"><?php echo $images[$j]['title']; ?></div>
						<?php } ?>
						</div>
					</li>
				<?php } ?>
			<?php } ?>
				</ul>
			</div>
		<?php } ?>
	<?php } else { ?>
		<div><div class="imgWrapper imgWrapper150"><img src="/img/skin/no_img_available.png" alt="No Image Available" /></div></div>
	<?php } ?>
	</div>
</div>
<?php } ?>
<?php $headers['js'] = '<script type="text/javascript">
	$(document).ready(function() {
		initFancyBox();
	});
	
	function previousPanel() {
		turnPage(-1);
	}
	
	function nextPanel() {
		turnPage(1);
	}
	
	function turnPage(i) {
		// determine if page-change is possible
		var currPage = 0;
		var totalPages = -1;
		var newPage;
		
		$(".galleryPanel").each(function(j, e) {
			if ($(e).is(":visible")) {
				currPage = j;
			}
			totalPages++;
		});
		
		newPage = currPage + i;
		
		if (newPage < 0) {
			newPage = 0;
		} else if (newPage > totalPages) {
			newPage = totalPages;
		}
		
		if (currPage != newPage) {
			$("#galleryPanel"+currPage).hide();
			$("#galleryPanel"+newPage).show();
		}
		
		// update nav
		if (newPage == 0) {
			hideNavButton("previous");
		} else {
			showNavButton("previous");
		}
		
		if (newPage == totalPages) {
			hideNavButton("next");
		} else {
			showNavButton("next");
		}
	}
	
	function hideNavButton(type) {
		if (type == "previous") {
			$("#previous").hide();
		} else if (type == "next") {
			$("#next").hide();
		}
	}
	
	function showNavButton(type) {
		if (type == "previous") {
			$("#previous").show();
		} else if (type == "next") {
			$("#next").show();
		}
	}
</script>'; ?>