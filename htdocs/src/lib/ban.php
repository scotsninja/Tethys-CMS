<?php
/**
 * Administer bans of user accounts, emails and IP addresses
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2011 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.0.1
 * @since      Class available since Release 0.0.1
 */
class Ban implements iTethysBase {

	/* PROPERTIES */
	
	private $id;				// id of row in db
	private $type;				// type of ban (email, user(id), ip)
	private $value;				// the value being banned
	private $adminId;			// the userId of the admin creating the ban
	private $notes;				// admin notes about the ban
	private $dateBanned;		// date the ban starts
	private $dateExpires;		// date the ban expires (if not set, it is a permanent ban)
	
	
	/* METHODS */
	
	public function __construct($id = 0, $type = null, $value = null, $adminId = 0, $notes = null, $dateBanned = null, $dateExpires = null) {
		$this->id = $id;
		$this->type = $type;
		$this->value = $value;
		$this->adminId = $adminId;
		$this->notes = $notes;
		$this->dateBanned = $dateBanned;
		$this->dateExpires = $dateExpires;
	}
	
	/* SEARCH */
	
	// search banned items based on (id, admin, keyword, type)
	// returns an array of Ban objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'banDateBanned DESC') {
		if (is_array($params)) {
			if (isset($params['id']) && is_numeric($params['id']) && $params['id'] > 0) {
				$whereParams['id'] = $params['id'];
				$where[] = "`banId`=:id";
			}
			if (isset($params['admin']) && is_numeric($params['admin']) && $params['admin'] > 0) {
				$whereParams['admin'] = $params['admin'];
				$where[] = "`banAdminId`=:admin";
			}
			if (isset($params['keyword']) && $params['keyword'] != '') {
				$whereParams['keyword'] = $params['keyword'];
				$where[] = "`banNotes` LIKE :keyword";
			}
			if (isset($params['type']) && in_array($params['type'], array('user', 'ip', 'email'))) {
				$whereParams['type'] = $params['type'];
				$where[] = "`banType`=:type";
			}
			if (isset($params['value']) && $params['value'] != '') {
				$whereParams['value'] = $params['value'];
				$where[] = "`banValue`=:value";
			}
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`banId`) AS result FROM `banned`";
		$query = "SELECT `banId` AS id, ".
				"`banType` AS type, ".
				"`banValue` AS value, ".
				"`banAdminId` AS adminId, ".
				"`banNotes` AS notes, ".
				"`banDateBanned` AS dateBanned, ".
				"`banDateExpires` AS dateExpires ".
				"FROM `banned`";
		
		$query .= $whereClause . addQuerySort($sort) . addQueryLimit($perPage, $pageNum);
		$totQ .= $whereClause;
		
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			$totalResults = 0;
			return false;
		}

		$totalResults = $GLOBALS['dbObj']->fetchResult($totQ, $whereParams);

		foreach ($results as $obj) {
			$resultArr[] = new Ban($obj['id'], $obj['type'], $obj['value'], $obj['adminId'], $obj['notes'], $obj['dateBanned'], $obj['dateExpires']);
		}

		return $resultArr;
	}
	
	// returns Ban object associated to specific banId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = Ban::search(array('id' => $id));
		
		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	// return array of Ban objects associated to banned user accounts
	public static function getUsers($search = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'banDateBanned DESC') {
		$search['type'] = 'user';
		
		return Ban::search($search, $perPage, $pageNum, $totalResults, $sort);
	}
	
	// return array of Ban objects associated to banned IP address
	public static function getIPs($search = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'banDateBanned DESC') {
		$search['type'] = 'ip';
		
		return Ban::search($search, $perPage, $pageNum, $totalResults, $sort);
	}
	
	// return array of Ban objects associated to banned emails
	public static function getEmails($search = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'banDateBanned DESC') {
		$search['type'] = 'email';
		
		return Ban::search($search, $perPage, $pageNum, $totalResults, $sort);
	}
	
	/* CREATE/EDIT/DELETE */
	
	public function __get($var) {
		if (isset($this->$var)) {
			return $this->$var;
		}
	}
	
	public function __set($var, $value) {
		switch($var) {
			case 'notes':
				$ret = $this->setNotes($value);
			break;
			case 'dateExpires':
			case 'expires':
				$ret = $this->setExpires($value);
			break;
			default:
				$ret = false;
			break;
		}
		
		if ($ret) {
			$this->$var = $value;
		}
		
		return $ret;
	}
	
	// create a new ban
	// if $expires is null, it is a permanent ban
	public static function add($type, $value, $expires = null, $notes = null) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_ERROR, 'You must be logged in as admin to ban users.');
			return false;
		}
		
		// validate data
		if ($type == 'user') {
			if (!is_numeric($value)) {
				SystemMessage::save(MSG_WARNING, 'Invalid user account.', 'value');
				$fail[] = true;
			}
		} else if ($type == 'email') {
			if (!isValidEmail($value)) {
				SystemMessage::save(MSG_WARNING, 'Invalid email address.', 'value');
				$fail[] = true;
			}
		} else if ($type == 'ip') {
			if (!isValidIP($value)) {
				SystemMessage::save(MSG_WARNING, 'Invalid IP address.', 'value');
				$fail[] = true;
			}
		} else {
			SystemMessage::save(MSG_WARNING, 'Invalid type.', 'type');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		if ($expires == '') {
			$expires = null;
		}
		
		// build the query and pass to the db
		$params = array(
			'type' => $type,
			'value' => $value,
			'adminId' => $GLOBALS['userObj']->id,
			'notes' => $notes,
			'banned' => date(DATE_SQL_FORMAT),
			'expires' => $expires
		);
		
		$query = "INSERT INTO `banned` (`banType`, `banValue`, `banAdminId`, `banNotes`, `banDateBanned`, `banDateExpires`) VALUES (:type, :value, :adminId, :notes, :banned, :expires)";
		
		try {
			return $GLOBALS['dbObj']->insert($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error banning '.$type.'.');
			return false;
		}
	}
	
	// remove ban from the db
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to unban that user.');
			return false;
		}
		
		$query = "DELETE FROM `banned` WHERE `banId`=:id";
		$params = array('id' => $this->id);
		
		try {
			return $GLOBALS['dbObj']->delete($query, $params);
		} catch (Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error removing ban.');
			return false;
		}
	}
	
	// update the ban's notes
	public function setNotes($notes = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to modify that ban.');
			return false;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `banned` SET `banNotes`=:notes WHERE `banId`=:id";
		$params = array('notes' => $notes,
			'id' => $this->id
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating ban notes.');
			return false;
		}
	}
	
	// update the ban's expiration date
	public function setExpires($expires = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to modify that ban.');
			return false;
		}
		
		if ($expires == '') {
			$expires = null;
		}
		
		// build the query and pass to the db
		$query = "UPDATE `banned` SET `banDateExpires`=:expires WHERE `banId`=:id";
		$params = array('expires' => $expires,
			'id' => $this->id
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating ban expiration date.');
			return false;
		}
	}
	
	/* PERMISSIONS */
	
	// returns true if active user has permission to perform passed function
	public function canEdit($function = null) {
		return User::isAdmin();
	}
	
	// returns true if the active user can view
	public function canView() {
		return $this->canEdit();
	}
	
	/* UTILTIES */
	
	public static function getNameById($id) {
			if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$query = "SELECT `banValue` AS result FROM `banned` WHERE `banId`=:id";
		$params = array('id' => $id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	public static function getList($sort = 'banDateBassed DESC', $showInactive = false) {
		// determine whether to return inactive page
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for active search-parameter
			if (!$showInactive) {
				$where[] = "`banDateExpires` IS NULL OR `banDateExpires`>=NOW()";
			}
		} // else, only return active pages
		else {
			$where[] = "`banDateExpires` IS NULL OR `banDateExpires`>=NOW()";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$query = "SELECT `banId` AS id, ".
				"`banType` AS type, ".
				"`banValue` AS value, ".
				"`banDateBanned` AS banned, ".
				"`banDateExpires` AS expires ".
				"FROM `banned`";
		
		$query .= $whereClause . addQuerySort($sort);

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('id' => $obj['id'], 'type' => $obj['type'], 'value' => $obj['value'], 'date-banned' => $obj['banned'], 'date-expires' => $obj['expires']);
		}
		
		return $resultArr;
	}
}

?>