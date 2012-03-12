<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

/* includes */
$includeParams['qtip'] = true;

/* meta tags */
$metaTitle = 'Contact | 20xx Productions';
$metaDescription = 'Contact page to request quotes, information, or leave a message.';
$metaKeywords = '20xx Productions, contact, quote';

require_once('recaptcha/recaptchalib.php');
$rcPublicKey = SITE_RECAPTCHA_PUBLIC_KEY;
$rcPrivateKey = SITE_RECAPTCHA_PRIVATE_KEY;

// do page processing
if (isset($_POST['hidSubmit'])) {
	$name = $_POST['txtName'];
	$email = $_POST['txtEmail'];
	$message = $_POST['txtMessage'];
	
	// process form
	$resp = recaptcha_check_answer ($rcPrivateKey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
	
	if (!$resp->is_valid) {
		SystemMessage::save(MSG_WARNING, 'The reCAPTCHA wasn\'t entered correctly.  Please try again.', 'captcha');
		$pass[] = false;
	}
	if ($name == '') {
		SystemMessage::save(MSG_WARNING, 'Please enter your name', 'name');
		$pass[] = false;
	}
	if ($message == '' ) {
		SystemMessage::save(MSG_WARNING, 'Please enter your message', 'message');
		$pass[] = false;
	}
	if ($email == '' || !isValidEmail($email)) {
		SystemMessage::save(MSG_WARNING, 'The supplied email is not a valid email address.', 'email');
		$pass[] = false;
	}
	
	if (!(is_array($pass) && in_array(false, $pass))) {
		// send email
		$to = CORE_WEBMASTER;
		$subject = 'Contact Form Submission';
		$headers = 'From: '.$email."\r\n";
		
		if (sendMail($to, $subject, $message, $headers)) {
			SystemMessage::save(MSG_SUCCESS, 'Thank you for your feedback.  I will get back to you as soon as possible.', 'g');
			header('Location: contact');
			exit();
		} else {
			$eMsg = "Error submitting form: \r\n";
			$eMsg .= 'Name: ' . $name . "\r\n";
			$eMsg .= 'Email: ' . $email . "\r\n";
			$eMsg .= 'Message: ' . $message . "\r\n";
			$eMsg .= 'Date: ' . date(DATE_SQL_FORMAT) . "\r\n";
			SystemMessage::log(MSG_ERROR, $eMsg);
			SystemMessage::save(MSG_WARNING, 'Error submitting form.  Please try again.', 'g');
		}
	}
} else {
	$name = null;
	$email = null;
	$message = null;
}

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output();

?>

<div class="twelvecol last">
	<h1>Contact</h1>
</div>
<div class="sevencol">
	<div class="box">
		<?php SystemMessage::output('g'); ?>
		<form method="POST" action="contact">
		<table class="tableForm">
			<tr>
				<td class="tdHeader tdWidth10"><label for="txtName">Name</label></td>
				<td><input type="text" name="txtName" id="txtName" required="required" value="<?php echo $name; ?>" size="40" tabindex="1" /> <?php echo Tooltip::outputInfo('Please provide your name so I know who I am talking to.'); ?><?php SystemMessage::output('name'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader"><label for="txtEmail">Email</label></td>
				<td><input type="email" name="txtEmail" id="txtEmail" required="required" value="<?php echo $email; ?>" size="40" tabindex="2" /> <?php echo Tooltip::outputInfo('Enter your email address so I can contact you.'); ?><?php SystemMessage::output('email'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader"><label for="txtMessage">Message</label></td>
				<td><textarea name="txtMessage" id="txtMessage" tabindex="3" rows="5" cols="60"><?php echo $message; ?></textarea><?php SystemMessage::output('message'); ?></td>
			</tr>
			<tr>
				<td class="tdHeader">&nbsp;</td>
				<td><div><?php echo recaptcha_get_html($rcPublicKey); ?><?php SystemMessage::output('captcha'); ?></div></td>
			</tr>
			<tr>
				<td colspan="2"><div style="text-align:right;"><input type="submit" value="Submit" name="btnSubmit" /></div></td>
			</tr>
		</table>
		<input type="hidden" name="hidSubmit" value="1" />
		</form>
	</div>
</div>

<?php include(CORE_INCLUDE_DIR.'footer.php'); ?>