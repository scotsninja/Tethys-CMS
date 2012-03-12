<?php
/**
 * Administer user profiles
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.2.0
 * @since      Class available since Release 0.2.0
 */

class Profile implements iTethysBase {

	/* PROPERTIES */
	
	private $id;				// userId
	private $firstName;
	private $middleName;
	private $lastName;
	private $location;
	private $bio;
	private $talents;
	private $avatar;
	
	private $userObj;			// stores the associated user object
	
	/* METHODS */
	
	public function __construct($id = 0, $firstName = null, $middleName = null, $lastName = null, $location = null, $bio = null, $talents = null, $avatar = null) {
		$this->id = $id;
		$this->firstName = $firstName;
		$this->middleName = $middleName;
		$this->lastName = $lastName;
		$this->location = $location;
		$this->bio = $bio;
		$this->talents = $talents;
		$this->avatar = $avatar;
		
		$this->userObj = User::getById($id);
	}
	
	/* SEARCH */
	
	// search profiles based on (id, name)
	// returns an array of Profile objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'profileLastName ASC, profileFirstName ASC') {
		// if search parameters are set, validate the specific parameters
		if (is_array($params)) {
			if (isset($params['id']) && is_numeric($params['id'])) {
				$whereParams['id'] = $params['id'];
				$where[] = "`profileId`=:id";
			}
			if (isset($params['name']) && $params['name'] != '') {
				$whereParams['name'] = $params['name'];
				$where[] = "(`profileFirstName` LIKE :name% OR `profileMiddleName` LIKE :name% OR `profileLastName` LIKE :name%)";
			}
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`profileId`) AS result FROM `profiles`";
		$query = "SELECT `profileId` AS id, ".
				"`profileFirstname` AS firstName, ".
				"`profileMiddleName` AS middleName, ".
				"`profileLastName` AS lastName, ".
				"`profileLocation` AS location, ".
				"`profileBio` AS bio, ".
				"`profileTalents` AS talents, ".
				"`profileAvatar` AS avatar ".
				"FROM `profiles`";
		
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
			$resultArr[] = new Profile($obj['id'], $obj['firstName'], $obj['middleName'], $obj['lastName'], $obj['location'], $obj['bio'], $obj['talents'], $obj['avatar']);
		}

		return $resultArr;
	}
	
	// returns Profile object associated to specific userId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = Profile::search(array('id' => $id));
		
		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	// returns Profile object associated to specific userId
	public static function getByUserId($user) {
		return Profile::getById($user);
	}
	
	/* CREATE/EDIT/DELETE */
	
	public function __get($var) {
		switch($var) {
			case 'name':
				$ret = $this->firstName.' '.$this->lastName;
			break;
			case 'fullName':
				$ret = $this->firstName.' '.$this->middleName.' '.$this->lastName;
			break;
			case 'email':
				$ret = $this->userObj->email;
			break;
			case 'facebookId':
				$ret = $this->userObj->facebookId;
			break;
			case 'dateJoined':
				$ret = $this->userObj->dateJoined;
			break;
			case 'imagePath':
				$ret = $this->getImagePath();
			break;
			default:
				if (isset($this->$var)) {
					$ret = $this->$var;
				}
			break;
		}
		
		return $ret;
	}
	
	public function __set($var, $value) {
		switch($var) {
			case 'name':
				if ($this->fullName != $value) {
					$ret = $this->setName($value);
				}
			break;
			case 'bio':
				if ($this->bio != $value) {
					$ret = $this->setBio($value);
				}
			break;
			case 'talents':
				if ($this->talents != $value) {
					$ret = $this->setTalents($value);
				}
			break;
			case 'location':
				if ($this->location != $value) {
					$ret = $this->setLocation($value);
				}
			break;
			case 'avatar':
				// @todo
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
	
	// creates a new profile
	public static function add($userId, $name, $location = null, $bio = null, $talents = null) {
		if (!is_numeric($userId) || $userId < 1) {
			SystemMessage::log(MSG_ERROR, 'Error creating profile: Invalid userId');
			$fail[] = true;
		}
		if ($name == '') {
			SystemMessage::log(MSG_ERROR, 'Error creating profile: Name is null');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		$nameArr = Profile::extractNames($name);
		
		// build the query and pass to the db
		$params = array(
			'user' => $userId,
			'fname' => $nameArr['first'],
			'mname' => $nameArr['middle'],
			'lname' => $nameArr['last'],
			'location' => $location,
			'bio' => $bio,
			'talents' => $talents
		);
		
		// add profile to db
		$query = "INSERT INTO `profiles` (`profileId`, `profileFirstName`, `profileMiddleName`, `profileLastName`, `profileLocation`, `profileBio`, `profileTalents`) VALUES (:user, :fname, :mname, :lname, :location, :bio, :talents)";
		
		try {
			return $GLOBALS['dbObj']->insert($query, $params);
		} catch(Exception $e) {
			throw new Exception('Profile not created.');
			return false;
		}
	}
	
	// remove a user profile
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		// delete queries
		$profileQ = "DELETE FROM `profiles` WHERE `profileId`=:id";			// remove profile
		
		$params = array('id' => $this->id);
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			$pass[] = $GLOBALS['dbObj']->delete($profileQ, $params);
			
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
	
	// set the name fields of the specific profile
	public function setName($name) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this profile.');
			return false;
		}
		
		// validate the account information
		if ($name == '') {
			SystemMessage::save(MSG_WARNING, 'Invalid name.', 'name');
			return false;
		}
		
		$nameArr = Profile::extractNames($name);

		// build the query and pass to the db
		$query = "UPDATE `profiles` SET `profileFirstName`=:fname, `profileMiddleName`=:mname, `profileLastName`=:lname WHERE `profileId`=:user";
		$params = array(
			'user' => $this->id,
			'fname' => $nameArr['first'],
			'mname' => $nameArr['middle'],
			'lname' => $nameArr['last']
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating name.', 'name');
			return false;
		}
	}
	
	// set the profile's bio field
	public function setBio($value = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this profile.');
			return false;
		}
		
		// validate data
		if (strlen($value) > 500) {
			SystemMessage::save(MSG_WARNING, 'You have exceeded the 500 character limit.', 'bio');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `profiles` SET `profileBio`=:val WHERE `profileId`=:user";
		$params = array(
			'user' => $this->id,
			'val' => $value
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating bio.', 'bio');
			return false;
		}
	}
	
	// set the profile's talents field
	public function setTalents($value = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this profile.');
			return false;
		}
		
		if (strlen($value) > 500) {
			SystemMessage::save(MSG_WARNING, 'You have exceeded the 500 character limit.', 'talents');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `profiles` SET `profileTalents`=:val WHERE `profileId`=:user";
		$params = array(
			'user' => $this->id,
			'val' => $value
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating talents.', 'talents');
			return false;
		}
	}
	
	// set the profile's location field
	public function setLocation($value = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this profile.');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `profiles` SET `profileLocation`=:val WHERE `profileId`=:user";
		$params = array(
			'user' => $this->id,
			'val' => $value
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating location.', 'location');
			return false;
		}
	}
	
	// upload an image and set the profile's avatar field
	public function setAvatar($image = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this profile.');
			return false;
		}
		
		// if the passed value is an uploaded file array, finish the upload (move to correct dir) and get the filepath
		if (is_array($image)) {
			try {
				$value = uploadImage(get_class($this), $this->id, $image);
			} catch (Exception $e) {
				SystemMessage::save(MSG_ERROR, $e->getMessage());
				return false;
			}
		} // else, set the filepath to null
		else {
			$value = '';
		}

		// build the query and pass to the db
		$query = "UPDATE `profiles` SET `profileAvatar`=:val WHERE `profileId`=:user";
		$params = array(
			'user' => $this->id,
			'val' => $value
		);
		
		try {
			if ($GLOBALS['dbObj']->update($query, $params)) {
				if ($this->avatar != '') {
					@unlink($this->imagePath);
				}
				return true;
			}
			
			return false;
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating avatar.', 'avatar');
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
			case 'delete':
				$ret = User::isAdmin();
			break;
			// is admin or the associated user
			default:
				$ret = (User::isAdmin() || ($this->id == $GLOBALS['userObj']->id));
			break;
		}
		
		return $ret;
	}
	
	public function canView() {
		return User::isLoggedIn();
	}
	
	/* UTILTIES */
	
	// returns filepath to the profile's avatar
	public function getImagePath() {
		$avatarPath = '/img/profiles/'.$this->id.'/';
		// if avatar has been upload
		if ($this->avatar != '') {
			$ret = $avatarPath.$this->avatar;
		} // else, no avatar but the user's facebook profile is linked, use their facebook avatar
		else if (is_numeric($this->facebookId) && $this->facebookId > 0) {
			$ret = 'https://graph.facebook.com/'. $this->facebookId .'/picture';
		} // else, use default avatar
		else {
			$ret = '/img/default_profile.jpg';
		}
		
		return $ret;
	}
	
	// takes a string and breaks on space into up to three separate strings
	// returns an array
	public static function extractNames($name) {
		if ($name == '') {
			return false;
		}
		
		$tempArr = explode(' ', $name);

		switch(count($tempArr)) {
			case 1:
				$fName = $tempArr[0];
				$mName = null;
				$lName = null;
			break;
			case 2:
				$fName = $tempArr[0];
				$mName = null;
				$lName = $tempArr[1];
			break;
			default:
				$fName = $tempArr[0];
				$mName = $tempArr[1];
				$lName = $tempArr[2];
			break;
		}
		
		return array('first' => $fName, 'middle' => $mName, 'last' => $lName);
	}
	
	// @todo
	public static function getNameById($id) {}
	public static function getList($sort = 'pageName ASC', $showInactive = false) {}
}

?>