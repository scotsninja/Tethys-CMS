<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

// select view/pagetitle
switch($_GET['v']) {
	case 'details':
		$view = 'details';
		$term = '';
		$pageTitle = 'Ban Details';
	break;
	case 'ip':
		$view = 'ip';
		$term = 'IP';
		$pageTitle = 'Banned IPs';
	break;
	case 'email':
		$view = 'email';
		$term = 'Email';
		$pageTitle = 'Banned Emails';
	break;
	case 'user':
	default:
		$view = 'user';
		$term = 'User';
		$pageTitle = 'Banned Users';
	break;
}

// if deleting ban
if ($_GET['t'] == 'delete') {
	$delB = Ban::getById($_GET['id']);
	
	if ($delB) {
		if ($delB->delete()) {
			SystemMessage::save(MSG_SUCCESS, 'Ban successfully removed.');
		} else {
			SystemMessage::save(MSG_ERROR, 'Error removing ban.');
		}
	} else {
		SystemMessage::save(MSG_WARNING, 'Could not delete ban because it does not exist.');
	}
	
	header('Location: admin_banned.php?v='.$view);
	exit();
}

if ($view == 'details') {
	if (is_numeric($_GET['id'])) {
		$tempB = Ban::getById($_GET['id']);
	}
	
	// setup variables
	if (isset($_POST['hidSubmit'])) {
		$type = $_POST['selType'];
		$value = $_POST['txtValue'];
		$notes = $_POST['txtNotes'];
		$expires = $_POST['dtExpires'];
	} else if ($tempB) {
		$type = $tempB->type;
		$value = $tempB->value;
		$notes = $tempB->notes;
		$expiresStr = $tempB->dateExpires;
		$expiresArr = explode(' ', $expiresStr);
		$expires = $expiresArr[0];
	} else {
		$type = (isset($_GET['type'])) ? $_GET['type'] : null;
		$value = null;
		$notes = null;
		$expires = null;
	}

	// if the form was posted
	if (isset($_POST['hidSubmit'])) {
		if ($tempB) {
			$pass[] = $tempB->setNotes($notes);
			$pass[] = $tempB->setExpires($expires);
			
			if (is_array($pass) && !in_array(false, $pass)) {
				SystemMessage::save(MSG_SUCCESS, 'Ban updated.');
				$loc  = 'admin_banned.php?v='.$type.'s';
			} else {
				SystemMessage::save(MSG_WARNING, 'Unable to save all fields.');
				$loc = 'admin_banned.php?v=details&id='.$tempB->id;
			}

			header('Location: ' . $loc);
			exit();
		} else {
			if (Ban::add($type, $value, $expires, $notes)) {
				SystemMessage::save(MSG_SUCCESS, 'Ban added.');
				$loc = 'admin_banned.php?v='.$type;
				
				header('Location: '.$loc);
				exit();
			} else {
				SystemMessage::save(MSG_ERROR, 'Error creating ban.');
			}
		}
	}
	
	$includes['js'] = '<script type="text/javascript" src="'.CORE_DIR_DEPTH.CORE_JS_DIR.'jquery-ui-timepicker-addon.js"></script>';
	$includes['js'] .= '<script type="text/javascript">
		$(function() {
			$("#dtExpires").datetimepicker({
				showButtonPanel: true,
				dateFormat: "yy-mm-dd",
				changeMonth: true,
				changeYear: true,
				maxDate: "+10y",
				minDate: new Date(),
				showOtherMonths: true
			});
		});
		
		function changeValueField(type) {
			if (type == "ip") {
				$("#valueField").html(\'<input type="text" name="txtValue" size="30" value="" />\');
			} else if (type == "email") {
				$("#valueField").html(\'<input type="text" name="txtValue" size="30" value="" />\');
			} else if (type == "user") {
				$("#valueField").html(\'<input type="text" name="txtValue" size="5" value="" />\');
			} else {
				$("#valueField").html("");
				alert("Error: Unknown type.");
			}
			
			initTooltips();
		}
	</script>';
} else {
	$perPage = (is_numeric($_GET['perpage']) && $_GET['perpage'] > 0) ? $_GET['perpage'] : 20;
	$pageNum = (is_numeric($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
	$sort = ($_GET['sort'] != '') ? $_GET['sort'] : 'banDateBanned DESC';
	
	$search = array('type' => $view);

	$bArr = Ban::search($search, $perPage, $pageNum, $totalResults, $sort);
}

$includes['css'] = '<style type="text/css">
	.tableForm textarea {
		width:100%;
	}
</style>';

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php'); ?>

<h1><?php echo $pageTitle; ?></h1>
<table width="100%" cellpadding="2" cellspacing="0" border="1">
	<tr>
		<td><a href="admin_banned.php?v=user" class="button button-blue">Banned Users</a></td>
		<td><a href="admin_banned.php?v=email" class="button button-blue">Banned Emails</a></td>
		<td><a href="admin_banned.php?v=ip" class="button button-blue">Banned IPs</a></td>
	</tr>
</table><br /><hr />

<?php SystemMessage::output(); ?>

<?php if ($view == 'details') { ?>
	<div class="box">
		<form method="POST" action="admin_banned.php?v=details<?php if ($tempB) { echo '&id='.$tempB->id; } ?>">
		<table class="tableForm">
			<tr>
				<td width="15%" class="tdHeader">Type:</td>
				<td><select name="selType" onchange="changeValueField(this.value);">
					<option value="">-Select-</option>
					<option value="email"<?php if ($type == 'email') { ?> selected="selected"<?php } ?>>Email</option>
					<option value="ip"<?php if ($type == 'ip') { ?> selected="selected"<?php } ?>>IP</option>
					<option value="user"<?php if ($type == 'user') { ?> selected="selected"<?php } ?>>User</option>
				</select> <?php echo Tooltip::outputInfo('What type of ban is this?'); ?><?php SystemMessage::output('type'); ?>
				</td>
			</tr>
			<tr>
				<td class="tdHeader">Value:</td>
				<td><div id="valueField">
					<?php if ($type == 'user') { ?>
						<input type="text" name="txtValue" size="5" value="<?php echo $value; ?>" />
					<?php } else { ?>
						<input type="text" name="txtValue" size="25" value="<?php echo $value; ?>" />
					<?php } ?>
					</div>
				<?php SystemMessage::output('value'); ?>
			</tr>
			<tr>
				<td class="tdHeader">Notes:</td>
				<td><textarea name="txtNotes" rows="4" cols="50"><?php echo $notes; ?></textarea><?php SystemMessage::output('notes'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader">Expires:</td>
				<td><input type="text" id="dtExpires" name="dtExpires" value="<?php echo $expires; ?>" /> <?php echo Tooltip::outputInfo('Select a date for a suspension.  If a date is selected, the ban will end at the specified time.  If no date is selected, the ban is permanent.'); ?><?php SystemMessage::output('expires'); ?></td>
			</tr>
			<tr>
				<td colspan="2"><div align="right"><input type="submit" value="Save" name="btnSubmit" /></div></td>
			</tr>
		</table>
		<input type="hidden" name="hidSubmit" value="1" />
		</form>
	</div>
<?php } else { ?>
	<div><a href="admin_banned.php?v=details&t=add&type=<?php echo $view; ?>" class="button button-blue">Ban <?php echo $term; ?></a></div>
	<div class="box">
		<form method="GET" action="admin_banned.php">
		<table class="tableNav">
			<tr>
				<td width="1%" nowrap="nowrap"><strong>Total:</strong> <?php echo number_format($totalResults, 0); ?> Banned</td>
				<td><?php $url = 'admin_banned.php?v='.$view;
					echo outputPagingLinks($url, $pageNum, $perPage, $totalResults); ?>
				</td>
				<td width="1%" nowrap="nowrap"><strong>Per Page:</strong> 
					<select name="perpage">
					<?php $i = 20;
					do { ?>
						<option value="<?php echo $i; ?>"<?php if ($perPage == $i) { ?> selected="selected"<?php } ?>><?php echo $i; ?></option>
					<?php $i += 20;
					} while ($i <= (100)); ?>
					</select>
				</td>
				<td width="1%" nowrap="nowrap"><input type="submit" value="Go" /></td>
			</tr>
		</table>
		<input type="hidden" name="v" value="<?php echo $view; ?>" />
		</form>
		<?php if ($bArr) { ?>
			<table class="tableResults">
				<tr>
					<th width="1%">Edit</th>
					<th><?php echo $term; ?></th>
					<th>From</th>
					<th>To</th>
					<th width="1%">Delete</th>
				</tr>
			<?php $totB = count($bArr);
			for ($i = 0; $i < $totB; $i++) {
				$dateBanned = new DateTime($bArr[$i]->dateBanned);
				$dateExpires = ($bArr[$i]->dateExpires != '' && $bArr[$i]->dateExpires != '0000-00-00 00:00:00') ? $bArr[$i]->dateExpires : '2099-12-31';
				$dateExpires = new DateTime($dateExpires);
				
				if ($view == 'users') {
					$tempUser = User::getById($bArr[$i]->value);
					$label = $tempUser->name . ' ('.$tempUser->email.')';
				} else {
					$label = $bArr[$i]->value;
				}
				?>
				<tr>
					<td nowrap="nowrap" class="tdCenter"><a href="admin_banned.php?v=details&t=edit&id=<?php echo $bArr[$i]->id; ?>"><img class="tdIcon" src="/img/icons/config.png" alt="Edit" /></a></td>
					<td nowrap="nowrap"><?php echo $label; ?></td>
					<td nowrap="nowrap"><?php echo $dateBanned->format(DATE_DISPLAY_FORMAT_DATE); ?></td>
					<td nowrap="nowrap"><?php echo $dateExpires->format(DATE_DISPLAY_FORMAT_DATETIME); ?></td>
					<td nowrap="nowrap" class="tdCenter"><a href="admin_banned.php?v=<?php echo $view; ?>&t=delete&id=<?php echo $bArr[$i]->id; ?>" onclick="return confirm('This will remove the ban on the <?php echo strtolower($term); ?>.  Are you sure you wish to continue?');"><img class="tdIcon" src="/img/icons/cancel.png" alt="Delete" /></a></td>
				</tr>
			<?php } ?>
			</table>
		<?php } else { ?>
			<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">No results returned.</div>
		<?php } ?>
	</div>
<?php } ?>

<?php include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php'); ?>