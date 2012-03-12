<?php
/* Copyright 20xx Productions */
extract($params);

if ($perRow < 1) {
	$perRow = 4;
}

// calculate number of rows
$numResults = count($results);
$numRows = ceil(($numResults/$perRow));

?>
<form method="GET" action="/<?php echo $actionUrl; ?>">
<table class="tableNav">
	<tr>
		<td class="tdWidth1 tdNowrap"><strong>Total:</strong> <?php echo number_format($totalResults, 0); ?> <?php echo ucwords($objectType); ?>s</td>
		<td><?php echo outputPagingLinks($actionUrl, $pageNum, $perPage, $totalResults); ?></td>
		<td class="tdWidth1 tdNowrap"><strong>Per Page:</strong> 
			<select name="perpage">
			<?php $i = $perRow*3;
			do { ?>
				<option value="<?php echo $i; ?>"<?php if ($perPage == $i) { ?> selected="selected"<?php } ?>><?php echo $i; ?></option>
			<?php $i += $perRow;
			} while ($i <= ($perRow*5)); ?>
			</select>
		</td>
		<td class="tdWidth1 tdNowrap"><input type="submit" value="Go" /></td>
	</tr>
</table>
</form>
<?php if ($results) { ?>
	<table class="tableResults">
	<?php for ($i = 0; $i < $numRows; $i++) { ?>
		<tr><?php
		for ($j = 0; $j < $perRow; $j++) {
			$index = ($i*$perRow)+$j;
			if ($i%2==0) {
				$tdClass = ($index%2==0) ? 'oddCell' : 'evenCell';
			} else {
				$tdClass = ($index%2==0) ? 'evenCell' : 'oddCell';
			}
			
			if ($results[$index]) { ?>
				<td class="tdTop <?php echo $tdClass; ?> tdWidth<?php echo floor(100/$perRow); ?>"><?php echo $results[$index]->outputSearchDetails($baseUrl); ?></td>
			<?php } else { ?>
				<td class="<?php echo $tdClass; ?>">&nbsp;</td>
			<?php } ?>
		<?php } ?>
		</tr>
	<?php } ?>
	</table>
<?php } else { ?>
	<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">No results returned.</div>
<?php } ?>