<?php
/* Copyright 20xx Productions */
extract($params);
?>
<dt><?php echo $title; ?></dt>
<dd>
	<div class="portfolioWrapper">
		<div class="preview">
		<?php if (is_array($images)) {
			$j = 0;
			foreach ($images as $i) { ?>
				<div class="imgWrapper"><a class="fbox-img" href="<?php echo $i['path']; ?>" title="<?php echo $i['caption']; ?>"><img src="<?php echo $i['path']; ?>" alt="<?php echo $i['title']; ?>" /></a></div>
				<?php if (++$j > 2) {
					break;
				}
			}
		} else { ?>
			<div class="imgWrapper"><img src="img/skin/no_img_available.png" alt="No Image Available" /></div>
		<?php } ?>
		</div>
		<div class="rightText">
		<h4><?php echo $name; ?></h4>
		<div class="item employer">(as <?php echo $employer; ?>)</div>
		<div class="item"><a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a></div>
		<div class="item"><strong>Technologies:</strong> <?php echo $technologies; ?></div>
		<div class="item"><strong>Purpose:</strong> <?php echo $purpose; ?></div>
		<div class="item"><strong>What I Did:</strong> <?php echo $did; ?></div>
		<div class="item"><strong>Challenges:</strong> <?php echo $challenges; ?></div>
		<div class="item"><strong>What I Learned:</strong> <?php echo $learned; ?></div>
		</div>
	</div>
</dd>