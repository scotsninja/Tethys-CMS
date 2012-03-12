<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

$saveEveryRow = 20;

// if the form was submitted
if (isset($_POST['hidSubmit'])) {
	if (is_array($_POST['params']) && is_array($_POST['hidParams'])) {
		$query = "UPDATE `settings` SET `settingValue`=:value WHERE `settingId`=:id";

		foreach ($_POST['params'] as $pKey => $pVal) {
			if ($GLOBALS['dbObj']->update($query, array('value' => $pVal, 'id' => $_POST['hidParams'][$pKey]))) {
				$pass[] = true;
			} else {
				SystemMessage::save(MSG_ERROR, 'Failed to save setting', 'msg_'.$_POST['hidParams'][$pKey]);
				$pass[] = false;
			}
		}
		
		if (is_array($pass) && !in_array(false, $pass)) {
			SystemMessage::save(MSG_SUCCESS, 'All settings saved successfully.');
		} else {
			SystemMessage::save(MSG_WARNING, 'Unable to successfully save all settings.');
		}
	} else {
		SystemMessage::save(MSG_WARNING, 'Invalid POST data');
	}
}

// retrieve info from config file
$query = "SELECT `settingId` AS id, `settingCategory` AS category, `settingName` AS name, `settingValue` AS value, `settingType` AS type, `settingDefault` AS def, `settingDescription` AS description FROM `settings`".addQuerySort('settingCategory, settingName');

$results  = $GLOBALS['dbObj']->select($query);

if (is_array($results)) {
	foreach ($results as $row) {
		// get constant name
		$settingName = $row['category'].' '.$row['name'];
		$settingName = strtoupper(str_replace(' ', '_', $settingName));
			
		$settings[] = array(
			'id' => $row['id'],
			'constant' => $settingName,
			'name' => $row['name'],
			'category' => $row['category'],
			'type' => $row['type'],
			'value' => $row['value'],
			'default' => $row['def'],
			'description' => $row['description']
		);
	}
}

$headers['js'] = '<script type="text/javascript" src="'.CORE_DIR_DEPTH.'ckeditor/ckeditor.js"></script>';

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php'); ?>

<h1>Configuration</h1>
<?php SystemMessage::output(); ?>
<?php if (is_array($settings)) { ?>
	<div class="box">
	<form action="admin_config.php" method="POST">
	<table class="tableSearch">
		<tr>
			<th width="30%">Setting</th>
			<th>Value</th>
			<th>Default</th>
		</tr>
	<?php $i = 0;
	foreach ($settings as $s) {
		if ($i++ % $saveEveryRow == 0) { ?>
			<tr>
			<td colspan="3"><div align="right"><input type="submit" value="Save Config" name="btnSubmit" /></div></td>
		</tr>
		<?php } else { ?>
			<tr>
				<td valign="top"><strong><?php echo $s['category']; ?>: <?php echo $s['name']; ?></strong><br />
					<em><?php echo $s['description']; ?></em>
				</td>
				<td valign="top"><?php if ($s['type'] == 'integer') { ?>
					<input type="text" name="params[<?php echo $s['name']; ?>]" size="10" value="<?php echo $s['value']; ?>" />
				<?php } else if ($s['type'] == 'string' || $s['type'] == 'path' || $s['type'] == 'email') { ?>
					<input type="text" name="params[<?php echo $s['name']; ?>]" size="40" value="<?php echo $s['value']; ?>" />
				<?php } else if ($s['type'] == 'boolean') { ?>
					<select name="params[<?php echo $s['name']; ?>]">
						<option value="false"<?php if ($s['value'] == 'false') { ?> selected="selected"<?php } ?>>False</option>
						<option value="true"<?php if ($s['value'] == 'true') { ?> selected="selected"<?php } ?>>True</option>
					</select>
				<?php } else if ($s['type'] == 'html') { ?>
					<?php echo outputCkEditor('params['.$s['name'].']', $s['value'], 'Full'); ?>
				<?php } else { ?>
					<textarea name="params[<?php echo $s['name']; ?>]" rows="3" cols="40"><?php echo $s['value']; ?></textarea></td>
				<?php } ?><?php SystemMessage::output('msg_'.$s['id']); ?>
					<input type="hidden" name="hidParams[<?php echo $s['name']; ?>]" value="<?php echo $s['id']; ?>" />
				</td>
				<td valign="top"><?php echo $s['default']; ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
		<tr>
			<td colspan="3"><div align="right"><input type="submit" value="Save Config" name="btnSubmit" /></div></td>
		</tr>
	</table>
	<input type="hidden" name="hidSubmit" value="1" />
	</form>
	</div>
<?php } else { ?>
	<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">There were no settings loaded.</div>
<?php } ?>

<?php include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php'); ?>