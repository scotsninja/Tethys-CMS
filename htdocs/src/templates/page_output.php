<?php
/* Copyright 20xx Productions */
extract($params);

// left column
if ($leftCol) {
	if ($leftCol instanceof Page) { ?>
		<div class="<?php echo Page::getGridClass($includeWidth); ?>"><?php echo $leftCol->output(true); ?></div>
	<?php } else if ($leftCol != 'this') { ?>
		<div class="<?php echo Page::getGridClass($includeWidth); ?>"><?php echo $leftCol; ?></div>
	<?php } else { ?>
		<div class="<?php echo Page::getGridClass($width); ?>"><?php echo $content ?>
		<?php if ($bottom) { ?>
			<div><?php echo $bottom; ?></div>
		<?php } ?>
		</div>
	<?php }
}

// right column
if ($rightCol) {
	if ($rightCol instanceof Page) { ?>
		<div class="<?php echo Page::getGridClass($includeWidth); ?> last"><?php echo $rightCol->output(true); ?></div>
	<?php } else if ($rightCol != 'this') { ?>
		<div class="<?php echo Page::getGridClass($includeWidth); ?> last"><?php echo $rightCol; ?></div>
	<?php } else { ?>
		<div class="<?php echo Page::getGridClass($width); ?> last"><?php echo $content; ?>
		<?php if ($bottom) { ?>
			<div><?php echo $bottom; ?></div>
		<?php } ?>
		</div>
	<?php }
} ?>