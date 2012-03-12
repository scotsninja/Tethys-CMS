<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

$view = ($_GET['v'] == 'details') ? 'details' : 'list';
$pageTitle = ($view == 'details') ? 'User Manage' : 'User Search';

// if viewing user account details
if ($view == 'details') {
	if (is_numeric($_GET['id'])) {
		$tempUser = User::getById($_GET['id']);
	}
	
	// setup variables
	if (isset($_POST['hidSubmit'])) {
		$email = $_POST['txtEmail'];
		$name = $_POST['txtName'];
		$level = $_POST['selLevel'];
	} else if ($tempUser) {
		$email = $tempUser->email;
		$name = $tempUser->fullName;
		$level = $tempUser->level;
		
		$profileObj = Profile::getById($tempUser->id);
	} else {
		$email = null;
		$name = null;
		$level = 'user';
	}
	
	$fbId = ($tempUser) ? $tempUser->facebookId : null;
	
	// if the form was posted
	if (isset($_POST['hidSubmit'])) {
		if ($tempUser) {
			$pass[] = true;
			
			if ($tempUser->email != $email) {
				$pass[] = $tempUser->setEmail($email);
			}
			if ($tempUser->level != $level) {
				$pass[] = $tempUser->setLevel($level);
			}
			if ($tempUser->fullName != $name) {
				$pass[] = $tempUser->setName($name);
			}

			// update password
			if ($_POST['txtPassword'] != '') {
				$pass[] = $tempUser->setPassword($_POST['txtPassword'], $_POST['txtCPassword']);
			}
			
			if (is_array($pass) && !in_array(false, $pass)) {
				SystemMessage::save(MSG_SUCCESS, 'User account updated.');
				$loc  = 'admin_users.php?v=list';
			} else {
				SystemMessage::save(MSG_WARNING, 'Unable to save all fields.');
				$loc = 'admin_users.php?v=details&id='.$tempUser->id;
			}

			header('Location: ' . $loc);
			exit();
		} else {
			if (User::add($email, $_POST['txtPassword'], $_POST['txtCPassword'], $level, $name)) {
				SystemMessage::save(MSG_SUCCESS, 'User account created.');
				$loc = 'admin_users.php?v=list';
				
				header('Location: '.$loc);
				exit();
			} else {
				SystemMessage::save(MSG_ERROR, 'Error creating user account.');
			}
		}
	}
} // else, view list of user accounts
else {
	if ($_GET['t'] == 'delete') {
		$delUser = User::getById($_GET['id']);
		
		if ($delUser) {
			if ($delUser->delete()) {
				SystemMessage::save(MSG_SUCCESS, 'User account successfully deleted.');
			} else {
				SystemMessage::save(MSG_ERROR, 'Error deleting user account.');
			}
		} else {
			SystemMessage::save(MSG_WARNING, 'Could not delete account because it does not exist.');
		}
		
		header('Location: admin_users.php?v=list');
		exit();
	}
	
	// build search array
	$search = array();
	
	$perPage = (is_numeric($_GET['perpage']) && $_GET['perpage'] > 0) ? $_GET['perpage'] : 20;
	$pageNum = (is_numeric($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
	$sort = ($_GET['sort'] != '') ? $_GET['sort'] : 'userEmail ASC';
	
	$userArr = User::search($search, $perPage, $pageNum, $totalResults, $sort);
}

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php'); ?>

<h1><?php echo $pageTitle; ?></h1>
<?php SystemMessage::output(); ?>
<?php if ($view == 'details') { ?>
	<div class="box" style="width:50%;">
		<form method="POST" action="admin_users.php?v=details<?php if ($tempUser) { echo '&id='.$tempUser->id; } ?>">
		<table class="tableForm">
			<tr>
				<td width="25%" class="tdHeader">Email:</td>
				<td><input type="email" name="txtEmail" size="35" value="<?php echo $email; ?>" required="required" /><?php SystemMessage::output('email'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader">Name:</td>
				<td><input type="text" name="txtName" size="35" value="<?php echo $name; ?>" required="required" /><?php SystemMessage::output('name'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader">Password:</td>
				<td><input type="password" name="txtPassword" size="35" value="" /><?php SystemMessage::output('password'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader" nowrap="nowrap">Confirm Password:</td>
				<td><input type="password" name="txtCPassword" size="35" value="" /></td>
			</tr>
			<tr>
				<td class="tdHeader">Level:</td>
				<td><select name="selLevel">
					<option value="admin"<?php if ($level == 'admin') { ?> selected="selected"<?php } ?>>Admin</option>
					<option value="user"<?php if ($level == 'user') { ?> selected="selected"<?php } ?>>User</option>
					</select> <?php echo Tooltip::outputInfo('Select the permission level of this user.'); ?><?php SystemMessage::output('level'); ?>
				</td>
			</tr>
		<?php if ($tempUser && $fbId > 0) { ?>
			<tr>
				<td class="tdHeader" nowrap="nowrap">Facebook ID:</td>
				<td><?php echo $fbId; ?> <?php echo Tooltip::outputInfo('If registered with facebook, this is the user\'s facebook ID.'); ?></td>
			</tr>
		<?php } ?>
			<tr>
				<td colspan="2" class="tdRight"><input type="submit" value="Save" name="btnSubmit" /></td>
			</tr>
		</table>
		<input type="hidden" name="hidSubmit" value="1" />
		</form>
	</div>
<?php } else { ?>
	<div><a href="admin_users.php?v=details&t=add" class="button button-blue">Add New User</a></div><br />
	<div class="box">
		<form method="GET" action="admin_users.php">
		<table class="tableNav">
			<tr>
				<td width="1%" nowrap="nowrap"><strong>Total:</strong> <?php echo number_format($totalResults, 0); ?> Users</td>
				<td><?php $url = 'admin_users.php?v=list';
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
		<input type="hidden" name="v" value="list" />
		</form>
		<?php if ($userArr) { ?>
			<table class="tableResults">
				<tr>
					<th width="1%">Edit</th>
					<th width="5%">Status</th>
					<th width="5%">FB</th>
					<th>Name</th>
					<th>Email</th>
					<th width="5%">Level</th>
					<th>Last Login/<br />Date Joined</th>
					<th width="1%">Delete</th>
				</tr>
			<?php foreach ($userArr as $u) {
				$dateJoined = new DateTime($u->dateJoined);
				$ll = $u->getLastLogin();
				$lastLogin = ($ll) ? new DateTime($ll) : null;
				$lastLogin = ($lastLogin) ? $lastLogin->format(DATE_DISPLAY_FORMAT_DATETIME) : null;
				$status = $u->getStatus();
				?>
				<tr>
					<td nowrap="nowrap" class="tdCenter"><a href="admin_users.php?v=details&t=edit&id=<?php echo $u->id; ?>"><img class="tdIcon" src="/img/icons/config.png" alt="Edit" /></a></td>
					<td nowrap="nowrap"><?php echo $status['status']; ?></td>
					<td nowrap="nowrap"><?php echo ($u->facebookId) ? 'Yes' : 'No'; ?></td>
					<td><?php echo $u->name;; ?></td>
					<td><?php echo $u->email; ?></td>
					<td nowrap="nowrap"><?php echo $u->level; ?></td>
					<td><?php echo $lastLogin; ?><br />
						<?php echo $dateJoined->format(DATE_DISPLAY_FORMAT_DATETIME); ?></td>
					<td nowrap="nowrap" class="tdCenter"><a href="admin_users.php?v=list&t=delete&id=<?php echo $u->id; ?>" onclick="return confirm('This will permanently delete the user account.  Are you sure you wish to continue?');"><img class="tdIcon" src="/img/icons/cancel.png" alt="Delete" /></a></td>
				</tr>
			<?php } ?>
			</table>
		<?php } else { ?>
			<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">No results returned.</div>
		<?php } ?>
	</div>
<?php } ?>

<?php include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php'); ?>