<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

/* meta tags */
$metaTitle = 'Retrieve Password';
$metaDescription = 'Retrieve a lost password by resetting it.';
$metaRobots = 'none';

if (User::isLoggedIn()) {
	header('Location: index.php');
	exit();
}

switch ($_GET['step']) {
	case 3:
		$pageTitle = 'Reset Password: Final Step';
		$instructions = 'Reset code has been confirmed. Select a new password.';
		$step = 3;
	break;
	case 2:
		$pageTitle = 'Reset Password: Enter Code';
		$instructions = 'Enter the reset code provided in the email sent to the address associated to your account.';
		$step = 2;
	break;
	case 1:
	default;
		$pageTitle = 'Reset Password: Step 1';
		$instructions = 'If you have forgotten your password, use may use this form to reset it. Enter the email address you used to register your account and instructions for resetting your password will be sent to you.';
		$step = 1;
	break;
}

// process form 

if ($step == 3) {
	if (!User::isValidResetCode($_GET['rc'])) {
		SystemMessage::save(MSG_WARNING, 'Invalid reset code', 'code');
		header('Location: rp.php?step=2');
		exit();
	}

	if (isset($_POST['hidSubmit'])) {
		if ($_POST['txtPassword'] == '') {
			SystemMessage::save(MSG_WARNING, 'Password cannot be null', 'password');
			$fail[] = true;
		}
		if ($_POST['txtPassword'] != $_POST['txtCPassword']) {
			SystemMessage::save(MSG_WARNING, 'Passwords do not match', 'password');
			$fail[] = true;
		}
		
		if (!(is_array($fail) && in_array(true, $fail))) {
			if (User::resetPassword($_GET['rc'], $_POST['txtPassword'], $_POST['txtCPassword'])) {
				SystemMessage::save(MSG_SUCCESS, 'Password successfully reset.');
				header('Location: login.php');
				exit();
			} else {
				SystemMessage::save(MSG_ERROR, 'There was an error resetting your password.');
			}
		}
	}
}

if ($step == 2) {
	if ($_GET['rc'] != '') {
		if (User::isValidResetCode($_GET['rc'])) {
			header('Location: rp.php?step=3&rc='.$_GET['rc']);
			exit();
		} else {
			SystemMessage::save(MSG_WARNING, 'Invalid reset code', 'code');
		}
	}
}

if ($step == 1 && isset($_POST['hidSubmit'])) {
	if ($_POST['txtUsername'] == '') {
		SystemMessage::save(MSG_WARNING, 'Please enter the email associated to your account', 'username');
		$fail[] = true;
	}
	
	if (!(is_array($fail) && in_array(true, $fail))) {
		$userObj = User::getByEmail($_POST['txtUsername']);
		
		if ($userObj && $userObj->facebookId > 0) {
			SystemMessage::save(MSG_WARNING, 'Your account is already associated to your facebook profile. You do not need to reset your password. Simply go to the <a href="login.php">login page</a> and connect with Facebook.');
			$loc = 'rp.php?step=1';
		} else if ($userObj) {
			$rt = User::sendPasswordReset($_POST['txtUsername']);
			if ($rt) {
				SystemMessage::save(MSG_SUCCESS, 'Check your email for instructions to complete your password reset.');
				$loc = 'rp.php?step=2';
			} else {
				SystemMessage::save(MSG_ERROR, 'There was an error sending the reset email.  Please try again.', 'username');
				$loc = 'rp.php?step=1';
			}
		} else {
			SystemMessage::save(MSG_ERROR, 'No user account associated to that email.', 'username');
			$loc = 'rp.php?step=1';
		}
		
		header('Location: '.$loc);
		exit();
	}
}

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output(); ?>
<h1><?php echo $pageTitle; ?></h1>
<div class="sixcol">
	<div class="box">
		<div class="instructionText"><?php echo $instructions; ?></div>
	</div>
</div>
<div class="sixcol last">
<?php if ($step == 3) { ?>
	<div class="box">
		<form method="POST" action="rp.php?step=3&rc=<?php echo $_GET['rc']; ?>">
		<table class="tableForm">
			<tr>
				<td width="10%" nowrap="nowrap" class="tdHeader"><label for="txtPassword">Password</label></td>
				<td><input type="password" name="txtPassword" id="txtPassword" value="" size="45" required="required" />
				</td>
			</tr>
			<tr>
				<td nowrap="nowrap" class="tdHeader"><label for="txtCPassword">Confirm</label></td>
				<td><input type="password" name="txtCPassword" id="txtCPassword" value="" size="45" required="required" />
					<?php SystemMessage::output('password'); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2"><div align="right"><input type="submit" value="Reset Password" name="btnSubmit" /></div></td>
			</tr>
		</table>
		<input type="hidden" name="hidSubmit" value="1" />
		</form>
	</div>
<?php } else if ($step == 2) { ?>
	<div class="box">
		<form method="GET" action="rp.php">
		<table class="tableForm">
			<tr>
				<td width="25%" nowrap="nowrap" class="tdHeader"><label for="txtCode">Reset Code</label></td>
				<td><input type="text" name="rc" id="txtCode" value="<?php echo $_GET['rc']; ?>" size="15" required="required" /><br />
					<?php SystemMessage::output('code'); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2"><div align="right"><input type="submit" value="Validate Code" name="btnSubmit" /></div></td>
			</tr>
		</table>
		<input type="hidden" name="step" value="2" />
		</form>
	</div>
<?php } else { ?>
	<div class="box">
		<form method="POST" action="rp.php?step=1">
		<table class="tableForm">
			<tr>
				<td width="10%" class="tdHeader"><label for="txtUsername">Email</label></td>
				<td><input type="email" name="txtUsername" id="txtUsername" value="" size="45" required="required" />
					<?php SystemMessage::output('username'); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2"><div align="right"><input type="submit" value="Send Email" name="btnSubmit" /></div></td>
			</tr>
		</table>
		<input type="hidden" name="hidSubmit" value="1" />
		</form>
	</div>
<?php } ?>
</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>