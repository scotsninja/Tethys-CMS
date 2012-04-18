<?php
/**
 * Administer user accounts
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.1.0
 * @since      Class available since Release 0.1.0
 */

class User implements iTethysBase {

	/* PROPERTIES */
	
	private $id;
	private $email;
	private $level;
	private $facebookId;
	private $dateJoined;
	
	private $profileObj;
	
	/* METHODS */
	
	public function __construct($id = 0, $email = null, $level = null, $facebookId = null, $dateJoined = null) {
		$this->id = $id;
		$this->email = $email;
		$this->level = $level;
		$this->facebookId = $facebookId;
		$this->dateJoined = $dateJoined;
	}
	
	/* SEARCH */
	
	// search user accounts based on (id, facebookId, email, level)
	// returns an array of User objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'userEmail ASC') {
		// if search parameters are set, validate the specific parameters
		if (is_array($params)) {
			if (isset($params['id']) && is_numeric($params['id']) && $params['id'] > 0) {
				$whereParams['id'] = $params['id'];
				$where[] = "`userId`=:id";
			}
			if (isset($params['facebookId']) && is_numeric($params['facebookId'])) {
				$whereParams['facebookId'] = $params['facebookId'];
				$where[] = "`userFacebookID`=:facebookId";
			}
			if (isset($params['level'])) {
				$whereParams['level'] = $params['level'];
				$where[] = "`userLevel`=:level";
			}
			if (isset($params['email'])) {
				$whereParams['email'] = $params['email'];
				$where[] = "`userEmail`=:email";
			}
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`userId`) AS result FROM `users`";
		$query = "SELECT `userId` AS id, ".
				"`userEmail` AS email, ".
				"`userLevel` AS level, ".
				"`userFacebookId` AS facebookId, ".
				"`userDateJoined` AS dateJoined ".
				"FROM `users`";
		
		$query .= $whereClause . addQuerySort($sort) . addQueryLimit($perPage, $pageNum);
		$totQ .= $whereClause;

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			$totalResults = 0;
			return false;
		}

		$totalResults = $GLOBALS['dbObj']->fetchResult($totQ, $whereParams);
		
		foreach ($results as $obj) {
			$resultArr[] = new User($obj['id'], $obj['email'], $obj['level'], $obj['facebookId'], $obj['dateJoined']);
		}
		
		return $resultArr;
	}
	
	// returns User object associated to specific userId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = User::search(array('id' => $id));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	// returns User object associated to specific email
	public static function getByEmail($email) {
		if ($email == '' || !isValidEmail($email)) {
			return false;
		}
		
		$tempArr = User::search(array('email' => $email));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	/* CREATE/EDIT/DELETE */
	
	public function __get($var) {
		switch ($var) {
			case 'name':
			case 'firstName':
			case 'middleName':
			case 'lastName':
			case 'fullName':
				if (!($this->profileObj && $this->profileObj instanceof Profile)) {
					$this->getProfile();
				}
				
				$ret = $this->profileObj->$var;
			break;
			case 'imagePath':
				$ret = $this->getImagePath();
			break;
			case 'facebookId':
				$ret = $this->facebookId;
			break;
			default:
				if (!($this->profileObj && $this->profileObj instanceof Profile)) {
					$this->getProfile();
				}
				
				// if the variable is not a member of the user object, check the associated profile
				$ret = (isset($this->$var)) ? $this->$var : $this->profileObj->$var;
			break;
		}
		
		return $ret;
	}
	
	public function __set($var, $value) {
		switch($var) {
			case 'email':
				if ($this->email != $value) {
					$ret = $this->setEmail($value);
				}
			break;
			case 'facebookId':
				if ($this->facebookId != $value) {
					$ret = $this->setFacebookId($value);
				}
			break;
			case 'level':
				if ($this->level != $value) {
					$ret = $this->setLevel($value);
				}
			break;
			case 'name':
				if ($this->fullName != $value) {
					$ret = $this->setName($value);
				}
			break;
			default:
				// do nothing
			break;
		}
		
		if ($ret) {
			$this->$var = $value;
		}
		
		return $ret;
	}
	
	// create a new, basic user account and profile
	public static function register($email, $passwd = null, $cpasswd = null, $facebookId = 0, $name = null, $accessKey = null, $location = null) {
		$fbRegistration = false;
		
		// validate the account information
		if (!isValidEmail($email)) {
			SystemMessage::save(MSG_WARNING, 'Invalid email address.');
			$fail[] = true;
		}
		if ($name == '') {
			SystemMessage::save(MSG_WARNING, 'You must enter your name.');
			$fail[] = true;
		}
		
		// if a valid facebook id is passed, password is irrelevant and the email mustn't be unique
		if ($facebookId != '' && $facebookId > 0) {
			$fbRegistration = true;
		} // if no facebook id is passed, then a password is required and the email must be unique
		else {
			if (!isUsernameAvailable($email)) {
				SystemMessage::save(MSG_WARNING, 'The selected username is not available.');
				$fail[] = true;
			}
			if (!isValidPassword($passwd)) {
				SystemMessage::save(MSG_WARNING, 'Invalid password.');
				$fail[] = true;
			}
			if ($passwd != $cpasswd) {
				SystemMessage::save(MSG_WARNING, 'Passwords do not match.');
				$fail[] = true;
			}
		}

		// access key is used to restrict beta access
		if (CORE_REQUIRE_ACCESS_KEY) {
			if (!isValidAccessKey($accessKey)) {
				SystemMessage::save(MSG_WARNING, 'Access key not valid.');
				$fail[] = true;
			}
		}
		
		// check if email is banned
		$banned = Ban::search(array('type' => 'email', 'value' => $email));

		if ($banned) {
			SystemMessage::save(MSG_ERROR, 'This email may not be used as it is currently on the banned list.');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		// if the user is registering through facebook, their email address might already be in the system associated to an existing account
		// search for the email and update the facebookId if it already exists
		if ($fbRegistration) {
			$query = "SELECT `userId` AS result FROM `users` WHERE `userEmail`=:email";
			$params = array('email' => $email);
			
			$uId = $GLOBALS['dbObj']->fetchResult($query, $params);
			
			// existing account found
			if ($uId) {
				try {
					// update facebookId
					$query = "UPDATE `users` set `userFacebookId`=:fbId WHERE `userId`=:id";
					$params = array('fbId' => $facebokId, 'id' => $uId);
					
					return $GLOBALS['dbObj']->update($query, $params);
				} catch(Exception $e) {
					SystemMessage::save(MSG_ERROR, 'Error creating user account.');
					return false;
				}
			}
		}
		
		// insert user info into db
		$query = "INSERT INTO `users` (`userEmail`, `userPassword`, `userFacebookId`, `userLevel`, `userDateJoined`, `userAccessKey`) VALUES (:email, :password, :facebookId, :level, :date, :key)";
		
		$params = array(
			'email' => $email,
			'password' => encryptPassword($passwd),
			'level' => 'user',
			'key' => $accessKey,
			'date' => $GLOBALS['dtObj']->format('now', DATE_SQL_FORMAT)
		);

		$params['facebookId'] = ($facebookId > 0) ? $facebookId : null;
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			$userId = $GLOBALS['dbObj']->insert($query, $params);

			if ($userId > 0) {
				if ($facebookId > 0) {
					// retrieve bio from facebook
					$fbProfile = $GLOBALS['fbObj']->api('/me','GET');
					
					$bio = ($fbProfile) ? $fbProfile['bio'] : null;
				}
				
				// create user profile
				if (Profile::add($userId, $name, $location, $bio, $talents)) {
					if (POINTS_SIGNUP_BONUS > 0) {
						$tempProfile = Profile::getById($userId);

						$tempProfile->addPoints(POINTS_SIGNUP_BONUS, 'Signup bonus');
					}
					
					$GLOBALS['dbObj']->commit();
					return true;
				}
			}

			$GLOBALS['dbObj']->rollBack();
			return false;
		} catch(Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			SystemMessage::save(MSG_ERROR, 'Error creating user account.');
			return false;
		}
	}
	
	// creates a new user account
	// only admins can use this function; use User::register() when creating new accounts for active users
	public static function add($email, $passwd = null, $cpasswd = null, $level = 'user', $name = null, $location = null) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_WARNING, 'You do not have permission to add user accounts.');
			return false;
		}
		
		// validate the account information
		if (!isValidEmail($email)) {
			SystemMessage::save(MSG_WARNING, 'Invalid email address.', 'email');
			$fail[] = true;
		}
		if (!isUsernameAvailable($email)) {
			SystemMessage::save(MSG_WARNING, 'The selected username is not available.', 'email');
			$fail[] = true;
		}
		if (!isValidPassword($passwd)) {
			SystemMessage::save(MSG_WARNING, 'Invalid password.', 'password');
			$fail[] = true;
		}
		if ($passwd != $cpasswd) {
			SystemMessage::save(MSG_WARNING, 'Passwords do not match.', 'password');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		// insert user info into db
		$query = "INSERT INTO `users` (`userEmail`, `userPassword`, `userFacebookId`, `userLevel`, `userDateJoined`) VALUES (:email, :password, :facebookId, :level, :date)";
		
		$params = array(
			'email' => $email,
			'password' => encryptPassword($passwd),
			'level' => 'user',
			'facebookId' => null,
			'date' => $GLOBALS['dtObj']->format('now', DATE_SQL_FORMAT)
		);

		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			$userId = $GLOBALS['dbObj']->insert($query, $params);
			
			if ($userId > 0) {
				// create user profile
				if (Profile::add($userId, $name, $location, $bio, $talents)) {
					$GLOBALS['dbObj']->commit();
					return $userId;
				}
			}

			$GLOBALS['dbObj']->rollBack();
			return false;
		} catch(Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			SystemMessage::save(MSG_ERROR, 'Error creating user account.');
			return false;
		}
	}
	
	// remove a user account
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		$query = "DELETE FROM `users` WHERE `userId`=:id";
		$params = array('id' => $id);
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			$pass[] = $GLOBALS['dbObj']->delete($query, $params);
			
			// remove profile
			$this->getProfile();	
			$pass[] = $this->profileObj->delete();
			
			if (is_array($pass) && !in_array(false, $pass)) {
				$GLOBALS['dbObj']->commit();
				return true;
			}
			
			$GLOBALS['dbObj']->rollBack();
			return false;
		} catch (Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			return false;
		}
	}
	
	// set the email address of the specific user account
	public function setEmail($email) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this account.');
			return false;
		}
		
		// validate the account information
		if (!(isValidEmail($email) && isUsernameAvailable($email))) {
			SystemMessage::save(MSG_WARNING, 'Invalid email address.', 'email');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `users` SET `userEmail`=:email WHERE `userId`=:id";
		$params = array('id' => $this->id,
			'email' => $email
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating user email.');
			return false;
		}
	}
	
	// set the email address of the specific user account
	public function setPassword($passwd, $cpasswd) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this account.');
			return false;
		}
		
		// validate the account information
		if (!isValidPassword($passwd)) {
			SystemMessage::save(MSG_WARNING, 'Invalid password.', 'password');
			return false;
		}
		if ($passwd != $cpasswd) {
			SystemMessage::save(MSG_WARNING, 'Passwords do not match.', 'password');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `users` SET `userPassword`=:password WHERE `userId`=:id";
		$params = array('id' => $this->id,
			'password' => encryptPassword($passwd)
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating user password.');
			return false;
		}
	}
	
	// set the user level of the specific user account
	public function setLevel($level = 'user') {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this account.');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `users` SET `userLevel`=:level WHERE `userId`=:id";
		$params = array('id' => $this->id,
			'level' => $level
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating user level.');
			return false;
		}
	}
	
	// set the facebookId of the specific user account
	// may be deprecated?
	public function setFacebookId($facebookId) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this account.');
			return false;
		}
		
		if (!is_numeric($facebookId)) {
			SystemMessage::save(MSG_WARNING, 'Invalid Facebook ID');
			$fail[] = true;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `users` SET `userFacebookId`=:facebookId WHERE `userId`=:id";
		$params = array('id' => $this->id,
			'facebookId' => $facebookId
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating user account.');
			return false;
		}
	}
	
	// update the user's name
	public function setName($name = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this account.');
			return false;
		}
		
		if (!($this->profileObj && $this->profileObj instanceof Profile)) {
			$this->getProfile();
		}
		
		return $this->profileObj->name = $name;
	}
	
	/* LOGIN/LOGOUT */
	
	// takes the userId stored in the session, loads the associated user and performs verification functions
	// returns User object if verification passes
	// otherwise, returns false
	public static function loadBySession() {
		if (!(is_numeric($_SESSION['userId']) && $_SESSION['userId'] > 0)) {
			return false;
		}
		
		$userObj = User::getById($_SESSION['userId']);

		if (!$userObj) {
			unset($_SESSION['userId']);
			return false;
		}
		
		// check if banned
		if ($userObj->isBanned()) {
			SystemMessage::save(MSG_ERROR, 'Unable to load user account.  Account has been suspended.');
			unset($_SESSION['userId']);
			unset($userObj);
			return false;
		}
		
		// generate hash to verify user is correct user
		$currentUserHash = md5($_SERVER['HTTP_USER_AGENT']);
		
		try {
			$query = "SELECT `uaClient` AS result FROM `user_activity` WHERE `uaAction`=:action AND `uaUserId`=:user".addQuerySort('uaDate DESC').addQueryLimit(1);
			$params = array('action' => 'success', 'user' => $userObj->id);
			
			$client = $GLOBALS['dbObj']->fetchResult($query, $params);
			
			if (!$client) {
				throw new Exception('Could not retrieve user client information');
			}
			
			$originalUserHash = md5($client);
		} catch(Exception $e) {
			SystemMessage::log(MSG_ERROR, 'Error validating user account: ' . $e->getMessage());
			unset($_SESSION['userId']);
			unset($userObj);
			return false;
		}

		if ($currentUserHash == $originalUserHash) {
			return $userObj;
		} else {
			unset($_SESSION['userId']);
			unset($userObj);
			return false;
		}
	}
	
	// loads user info associated to username, and verifies password
	// sets userId in session and returns true if verified
	// otherwise, returns false
	public static function login($uname, $password) {
		// validate passed info
		if ($uname == '') {
			SystemMessage::save(MSG_WARNING, 'Enter your username.', 'username');
			$fail[] = true;
		}
		if ($password == '') {
			SystemMessage::save(MSG_WARNING, 'Enter your password', 'password');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			User::recordLogin($uname, null, 'failure');
			return false;
		}
		
		// retrieve user account matching username
		$query = "SELECT `userId` AS id, ".
				"`userEmail` AS email, ".
				"`userPassword` AS password, ".
				"`userLevel` AS level, ".
				"`userFacebookId` AS facebookId, ".
				"`userDateJoined` AS dateJoined ".
				"FROM `users` WHERE `userEmail`=:uname";

		$params = array('uname' => $uname);

		// execute the query
		$row = $GLOBALS['dbObj']->select($query, $params);
		
		if (!$row) {
			SystemMessage::save(MSG_WARNING, 'Username does not match records.', 'username');
			User::recordLogin($uname, null, 'failure');
			return false;
		}
		
		$obj = $row[0];
		
		$tempUser = new User($obj['id'], $obj['email'], $obj['level'], $obj['facebookId'], $obj['dateJoined']);
		
		// verify password
		if ($obj['password'] != encryptPassword($password)) {
			SystemMessage::save(MSG_WARNING, 'Password does not match password on file.', 'password');
			User::recordLogin($uname, $tempUser->id, 'failure');
			unset($tempUser);
			return false;
		}
		
		// check if banned
		if ($tempUser->isBanned()) {
			SystemMessage::save(MSG_ERROR, 'Unable to load user account.  Account has been suspended.');
			User::recordLogin($uname, $tempUser->id, 'failure');
			unset($tempUser);
			return false;
		}
		
		// set session variable
		$_SESSION['userId'] = $tempUser->id;

		// record successful login
		User::recordLogin($uname, $tempUser->id, 'success');

		return true;
	}
	
	// loads user info associated to fbId of visitor
	// sets userId in session and returns true if verified
	// otherwise, returns false
	public static function loginByFb() {
		$fbId = $GLOBALS['fbObj']->getUser();
		
		if (!is_numeric($fbId) || $fbId < 1) {
			return false;
		}
		
		// retrieve user account matching username
		$query = "SELECT `userId` AS id, ".
				"`userEmail` AS email, ".
				"`userPassword` AS password, ".
				"`userLevel` AS level, ".
				"`userFacebookId` AS facebookId, ".
				"`userDateJoined` AS dateJoined ".
				"FROM `users` WHERE `userFacebookId`=:fbId";

		$params = array('fbId' => $fbId);
		
		// execute the query
		$row = $GLOBALS['dbObj']->select($query, $params);
		
		if (!$row) {
			return false;
		}
		
		$obj = $row[0];
		
		$tempUser = new User($obj['id'], $obj['email'], $obj['level'], $obj['facebookId'], $obj['dateJoined']);
		
		// check if banned
		if ($tempUser->isBanned()) {
			SystemMessage::save(MSG_ERROR, 'Unable to load user account.  Account has been suspended.');
			User::recordLogin($tempUser->email, $tempUser->id, 'failure');
			unset($tempUser);
			return false;
		}
		
		// set session variable
		$_SESSION['userId'] = $tempUser->id;

		// record successful login
		User::recordLogin($tempUser->email, $tempUser->id, 'success');

		return true;
	}
	
	// log user out
	// sets userId in session and userObj to null
	public static function logout() {
		if (User::isLoggedIn()) {
			User::recordLogin($GLOBALS['userObj']->email, $GLOBALS['userObj']->id, 'logout');
			$_SESSION['userId'] = -1;
			unset($GLOBALS['userObj']);
		}
		
		return true;
	}
	
	/* PASSWORD RECOVERY/RESET */
	
	// generates a password reset code and sends instructional email to user
	public static function sendPasswordReset($email) {
		if ($email == '' || !isValidEmail($email)) {
			return false;
		}
		
		$tempUser = User::getByEmail($email);
		
		if (!$tempUser) {
			SystemMessage::save(MSG_ERROR, 'No user account associated to that email');
			return false;
		}
		
		do {
			// generate reset code
			$rc = $tempUser->generateResetCode();
			
			// store in db
			$sc = $tempUser->setResetCode($rc);
		} while (!$sc);
		
		// send email to user
		$subject = 'Password Reset Requested at '.SITE_TITLE;
		$message = "A password reset request was received for this account.  If you did not request this, you may ignore the rest of this email (your access to the site will not be affected).\n\n";
		$message .= "To reset your password, click the link below:\n\n";
		$message .= CORE_DOMAIN."rp.php?step=2&rc=".$rc."\n\n";
		$message .= "Or, you may copy and paste the reset code:\n\n";
		$message .= $rc . "\n\n";
		$message .= "Then, follow the on-screen instructions to complete the password reset.";
		
		return sendMail($tempUser->email, $subject, $message);
	}
	
	// generates and returns a random password reset code
	public function generateResetCode() {
		return substr(md5($this->id.time().mt_rand().$this->dateJoined), 3, 8);
	}
	
	// stores password reset code in db
	public function setResetCode($code = null) {
		$query = "UPDATE `users` SET `userResetCode`=:code WHERE `userId`=:id";
		$params = array('id' => $this->id, 'code' => $code);
		
		return $GLOBALS['dbObj']->update($query, $params);
	}
	
	// returns true if the passed code is currently assigned to a user
	public static function isValidResetCode($code) {
		if ($code == '') {
			return false;
		}
		
		$query = "SELECT `userId` AS result FROM `users` WHERE `userResetCode`=:code";
		$params = array('code' => $code);
		
		$ret = $GLOBALS['dbObj']->fetchResult($query, $params);

		return ($ret) ? true : false;
	}
	
	// returns true if the password has been updated
	public static function resetPassword($code, $passwd = null, $cpasswd = null) {
		if (!User::isValidResetCode($code)) {
			return false;
		}
		
		if (!isValidPassword($passwd)) {
			SystemMessage::save(MSG_WARNING, 'Invalid password.', 'password');
			return false;
		}
		if ($passwd != $cpasswd) {
			SystemMessage::save(MSG_WARNING, 'Passwords do not match.', 'password');
			return false;
		}
		
		// get user obj
		$query = "SELECT `userId` AS result FROM `users` WHERE `userResetCode`=:code";
		$params = array('code' => $code);
		
		$userId = $GLOBALS['dbObj']->fetchResult($query, $params);
		
		$tempUser = User::getById($userId);
		
		if (!$tempUser) {
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `users` SET `userPassword`=:password WHERE `userId`=:id";
		$params = array('id' => $tempUser->id,
			'password' => encryptPassword($passwd)
		);
		
		try {
			if ($GLOBALS['dbObj']->update($query, $params)) {
				$tempUser->setResetCode(null);
				return true;
			}
			
			return false;
		} catch(Exception $e) {
			return false;
		}
	}
	
	/* PERMISSIONS */
	
	// returns true if active user has permission to perform passed function
	public function canEdit($function = null) {
		if (!User::isLoggedIn()) {
			return false;
		}
		
		switch($function) {
			// admin-only
			case 'setLevel':
			case 'delete':
				$ret = User::isAdmin();
			break;
			// is admin or the associated user 
			case 'setEmail':
			case 'setFacebookId':
			case 'setPassword':
			case 'setName':
				$ret = (User::isAdmin() || $this->id == $GLOBALS['userObj']->id);
			break;
			default:
				$ret = false;
			break;
		}
		
		return $ret;
	}
	
	/* UTILITIES */
	
	// loads the associated profile info $this->profileObj
	private function getProfile() {
		$this->profileObj = Profile::getById($this->id);
	}
	
	// returns true if the active user is logged in
	public static function isLoggedIn() {
		return ($GLOBALS['userObj'] && ($GLOBALS['userObj'] instanceof User));
	}
	
	// returns true if the active user is an admin (or super-admin)
	public static function isAdmin() {
		return (User::isLoggedIn() && ($GLOBALS['userObj']->level == 'admin' || $GLOBALS['userObj']->level == 'super-admin'));
	}
	
	// return true if the user is currently banned (id, email or IP)
	// @todo: need to redo the way the banned items are stored and retrieved, but this works for now
	public function isBanned() {
		$banned = false;
		
		// check for banned IP
		$ipBans = Ban::search(array('type' => 'ip', 'value' => $_SERVER['REMOTE_ADDR']));
		
		if (is_array($ipBans)) {
			foreach ($ipBans as $ban) {
				// if no expiry date is set, it is a permanent ban
				if ($ban->dateExpires == '' || $ban->dateExpires == '0000-00-00 00:00:00') {
					return true;
					break;
				} // if expiry date is set, it is a suspension, so need to check if it is in the past or future
				else {
					if ($GLOBALS['dtObj']->comp('now', $ban->dateExpires) < 0) {
						return true;
						break;
					}
				}
			}
		}
		
		// check for banned userid
		$userBans = Ban::search(array('type' => 'user', 'value' => $this->id));
		
		if (is_array($userBans)) {
			foreach ($userBans as $ban) {
				// if no expiry date is set, it is a permanent ban
				if ($ban->dateExpires == '' || $ban->dateExpires == '0000-00-00 00:00:00') {
					return true;
					break;
				} // if expiry date is set, it is a suspension, so need to check if it is in the past or future
				else {
					if ($GLOBALS['dtObj']->comp('now', $ban->dateExpires) < 0) {
						return true;
						break;
					}
				}
			}
		}
		
		// check for banned email
		$emailBans = Ban::search(array('type' => 'email', 'value' => $this->email));
		
		if (is_array($emailBans)) {
			foreach ($emailBans as $ban) {
				// if no expiry date is set, it is a permanent ban
				if ($ban->dateExpires == '' || $ban->dateExpires == '0000-00-00 00:00:00') {
					return true;
					break;
				} // if expiry date is set, it is a suspension, so need to check if it is in the past or future
				else {
					if ($GLOBALS['dtObj']->comp('now', $ban->dateExpires) < 0) {
						return true;
						break;
					}
				}
			}
		}
		
		return false;
	}
	
	// set the minimum user level required to view the page
	// $redirect sets the page to redirect the user to a specific page
	// $message is the error message displayed to the user after the redirect
	public static function requireLogin($level = null, $redirect = null, $message = null) {
		if ($redirect == '' || !file_exists($redirect)) {
			$redirect = 'login.php';
		}
		
		if ($message == '') {
			$message = 'You do not have permission to view that page.';
		}
		
		if ($level != '') {
			switch ($level) {
				case 'super-admin':
					$pass = $GLOBALS['userObj']->level == 'super-admin';
				break;
				case 'admin':
					$pass = User::isAdmin();
				break;
				case 'moderator':
					$pass = $GLOBALS['userObj']->level != 'user';
				break;
				case 'user':
				default:
					$pass = User::isLoggedIn();
				break;
			}
		}
		
		if ($pass) {
			return true;
		} else {
			if ($message != '') {
				SystemMessage::save(MSG_ERROR, $message);
			}
			
			header('Location: ' . $redirect);
			exit();
		}
	}
	
	// returns the current status of the user account (active, suspended, banned)
	// @todo: need to redo the way the banned items are stored and retrieved, but this works for now
	public function getStatus() {
		if (!$this->isBanned()) {
			return array('status' => 'active', 'description' => null);
		}
		
		// retrieve bans on user account and email address
		$userBans = Ban::search(array('type' => 'user', 'value' => $this->id));
		$emailBans = Ban::search(array('type' => 'email', 'value' => $this->email));
		
		if (is_array($userBans)) {
			foreach ($userBans as $ban) {
				// if expiry date is set, it is a suspension, so need to check if it is in the past or future
				if ($ban->dateExpires != '' & $ban->dateExpires != '0000-00-00 00:00:00') {
					if ($GLOBALS['dtObj']->comp('now', $ban->dateExpires) < 0) {
						$statusArr = array('status' => 'suspended', 'description' => $ban->notes);
					}
				} // if no expiry date is set, it is a permanent ban
				else if ($ban->dateExpires == '' || $ban->dateExpires == '0000-00-00 00:00:00') {
					$statusArr = array('status' => 'banned', 'description' => $ban->notes);
					break;
				}
			}
		}
		
		if (is_array($emailBans)) {	
			foreach ($emailBans as $ban) {
				// if expiry date is set, it is a suspension, so need to check if it is in the past or future
				if ($ban->dateExpires != '' & $ban->dateExpires != '0000-00-00 00:00:00') {
					if ($GLOBALS['dtObj']->comp('now', $ban->dateExpires) < 0) {
						$statusArr = array('status' => 'suspended', 'description' => $ban->notes);
					}
				} // if no expiry date is set, it is a permanent ban
				else if ($ban->dateExpires == '' || $ban->dateExpires == '0000-00-00 00:00:00') {
					$statusArr = array('status' => 'banned', 'description' => $ban->notes);
					break;
				}
			}
		}
		
		return $statusArr;
	}
	
	// returns the date of the last successful user login
	// or, false if no logins are recorded
	public function getLastLogin() {
		$query = "SELECT `uaDate` AS result FROM `user_activity` WHERE `uaUserId`=:id ORDER BY `uaDate` DESC LIMIT 1";
		$params = array('id' => $this->id);
		
		try {
			return $GLOBALS['dbObj']->fetchResult($query, $params);
		} catch(Exception $e) {
			return null;
		}
	}
	
	// store user information at time of login
	public static function recordLogin($uname = null, $uid = 0, $action = 'failure') {
		$query = "INSERT INTO `user_activity` (`uaAction`, `uaUserId`, `uaUsername`, `uaIPAddress`, `uaClient`, `uaDate`) VALUES (:action, :id, :uname, :ip, :client, :date)";
		
		$params = array(
			'action' => $action,
			'id' => $uid,
			'uname' => $uname,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'client' => $_SERVER['HTTP_USER_AGENT'],
			'date' => $GLOBALS['dtObj']->format('now', DATE_SQL_FORMAT)
		);
		
		try {
			return $GLOBALS['dbObj']->insert($query, $params);
		} catch(Exception $e) {
			return false;
		}
	}
	
	// return total number of registered users
	public static function getNumUsers() {
		$query = "SELECT COUNT(`userId`) AS result FROM `users`";
		
		return $GLOBALS['dbObj']->fetchResult($query);
	}
	
	/* PROFILE */
	
	// returns path to proile avatar
	public function getImagePath() {
		if (!$this->profileObj) {
			$this->getProfile();
		}
		
		return $this->profileObj->getImagePath();
	}
	
	public static function getList($sort = 'userEmail ASC', $showInactive = false) {
		// build the search query
		$query = "SELECT `userId` AS id, ".
				"`userEmail` AS email, ".
				"`userLevel` AS level, ".
				"`userDateJoined` AS date ".
				"FROM `users`";
		
		$query .= addQuerySort($sort);

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('id' => $obj['id'], 'email' => $obj['email'], 'level' => $obj['level'], 'date-joined' => $obj['date']);
		}
		
		return $resultArr;
	}
	
	public static function getNameById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$query = "SELECT `userEmail` AS result FROM `users` WHERE `userId`=:id";
		$params = array('id' => $id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
}

?>