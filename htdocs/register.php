<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

/* meta tags */
$includes['meta']['title'] = 'Register';
$includes['meta']['robots'] = 'none';


if (User::isLoggedIn()) {
	header('Location: index.php');
	exit();
}

// process fb signed request
$fbSignedRequest = $GLOBALS['fbObj']->getSignedRequest();

if (is_array($fbSignedRequest['registration'])) {
	$fbId = (is_numeric($fbSignedRequest['user_id'])) ? $fbSignedRequest['user_id'] : 0;

	// verify metadata matches fields
	$regMetaData = json_decode(str_replace("'", '"', $fbSignedRequest['registration_metadata']['fields']), true);

	$pass[] = ($regMetaData[0] == array('name' => 'name'));
	$pass[] = ($regMetaData[1] == array('name' => 'email'));
	$pass[] = ($regMetaData[2] == array('name' => 'password', 'view' => 'not_prefilled'));
	$pass[] = ($regMetaData[3] == array('name' => 'access_key', 'description' => 'Access Key', 'type' => 'text'));

	if (is_array($pass) && !in_array(false, $pass)) {
		if (User::register($fbSignedRequest['registration']['email'], $fbSignedRequest['registration']['password'], $fbSignedRequest['registration']['password'], $fbId, $fbSignedRequest['registration']['name'], $fbSignedRequest['registration']['access_key'])) {
			SystemMessage::save(MSG_SUCCESS, 'Congratulations!  Your account has been created.');
			
			// login new user
			if (User::login($fbSignedRequest['registration']['email'], $fbSignedRequest['registration']['password'])) {
				$loc = 'register.php?step=2';
			} else {
				$loc = 'login.php';
			}
			
			// redirect to homepage
			header('Location: '.$loc);
			exit();
		}
	}
}

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output(); ?>

<h1>Signup</h1>
<div class="sixcol">
	<div class="box">
	<fb:registration 
		fields="[
			{'name':'name'},
			{'name':'email'},
			{'name':'password', 'view':'not_prefilled'},
			{'name':'access_key', 'description':'Access Key', 'type':'text'},
			{'name':'captcha'}
		]" 
		redirect-uri="<?php echo CORE_DOMAIN; ?>register.php"
		width="525">
	</fb:registration>
	</div>
</div>
<div class="sixcol last">
	<div class="box">
		<div class="heading">Create an account:</div>
		You may link 20xx Productions to your facebook account by logging in to facebook and clicking 'Register'.<br />
		Or, use your email address and password to create your 20xx Productions account.
	</div>
</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>