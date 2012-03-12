<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

/* meta tags */
$metaTitle = 'Login';
$metaRobots = 'none';

if ($_SERVER['HTTP_REFERER'] != '') {
	if (preg_match('~^'.CORE_DOMAIN.'~', $_SERVER['HTTP_REFERER'])) {
		$urlArr = explode('/', $_SERVER['HTTP_REFERER']);
		$redirect = $urlArr[3];
	}
} else {
	$redirect = 'homepage';
}

if (User::isLoggedIn()) {
	header('Location: '.$redirect);
	exit();
}

// check if user is logged in to fb
if ($GLOBALS['fbObj'] && $GLOBALS['fbObj']->getUser() > 0) {
	// if registerd, login to site and redirect to homepage
	if (User::loginByFb()) {
		$loc = $redirect;
	} else {
		$loc = 'register.php';
	}

	header('Location: '.$loc);
	exit();
}

// process form
if (isset($_POST['hidSubmit'])) {
	$uname = $_POST['txtUsername'];
	
	if ($uname == '') {
		SystemMessage::save(MSG_WARNING, 'Enter your username.', 'username');
		$fail[] = true;
	}
	if ($_POST['txtPassword'] == '') {
		SystemMessage::save(MSG_WARNING, 'Enter your password', 'password');
		$fail[] = true;
	}
	
	if (!(is_array($fail) && in_array(true, $fail))) {
		if (User::login($uname, $_POST['txtPassword'])) {
			$loc = ($_POST['hidRedirect']) ? $_POST['hidRedirect'] : 'homepage';
			header('Location: '.$loc);
			exit();
		}
	}
} else {
	$uname = null;
}

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output(); ?>
<h1>Login</h1>
<div class="sixcol">
	<div class="box">
	<form method="POST" action="login.php">
	<table class="tableForm">
		<tr>
			<td width="50%"><p><em>Connect with Facebook:</em><br /><br /> <fb:login-button autologoutlink="true" onlogin="window.location.reload();"></fb:login-button></p><br /></td>
			<td><p><em>Or, enter your email address and password below:</em></p>
				<div style="margin:10px 0px;">
					<label for="txtUsername">Email</label><br />
					<input type="email" name="txtUsername" id="txtUsername" value="<?php echo $uname; ?>" size="30" required="required" />
					<?php SystemMessage::output('username'); ?>
				</div>
				<div style="margin:10px 0px;">
					<label for="txtPassword">Password</label><br />
					<input type="password" name="txtPassword" id="txtPassword" value="" size="30" required="required" />
					<?php SystemMessage::output('password'); ?>
				</div>
				<div align="right"><input type="submit" value="Login" name="btnSubmit" /> | <a href="rp.php">Forgot Password?</a></div>
			</td>
		</tr>
	</table>
	<input type="hidden" name="hidRedirect" value="<?php echo $redirect; ?>" />
	<input type="hidden" name="hidSubmit" value="1" />
	</form>
	</div>
</div>
<div class="sixcol last">&nbsp;</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>