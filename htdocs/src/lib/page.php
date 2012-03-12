<?php
/**
 * Administer static pages
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.5.0
 * @since      Class available since Release 0.5.0
 */

class Page extends Outputtable implements iTethysBase {

	/* PROPERTIES */
	
	private $id;
	private $userId;						// the userId of the user who created the page
	private $name;							// page name, used when searching
	private $title;							// value display in <title> tags
	private $description;					// meta description value
	private $keywords;						// meta keywords value
	private $author;						// meta author value
	private $url;							// custom url for the page
	private $file;							// @deprecated
	private $protected;						// if set to 1, only a super-admin can delete
	private $userLevel;						// minimum user level required to view the page
	private $comments;						// if set to 1, enable comment form on the page
	private $width;							// width in columns (1-12) of the page
	private $includePage;					// pageId of another page to include as a sidebar
	private $includePosition;				// position (left, right, bottom), relative to the parent page, to display the included page
	private $dateAdded;
	private $active;
	
	private $value;							// the page text
	private $css;							// extra CSS for the page (included in a <style> tag)
	
	/* METHODS */
	
	public function __construct($id = 0, $userId = null, $name = null, $title = null, $description = null, $keywords = null, $author = null, $url = null, $file = null, $protected = 1, $userLevel = 'admin', $comments = 'none', $width = 12, $includePage = null, $includePosition = null, $dateAdded = null, $active = 0) {
		$this->id = $id;
		$this->userId = $userId;
		$this->name = $name;
		$this->title = $title;
		$this->description = $description;
		$this->keywords = $keywords;
		$this->author = $author;
		$this->url = $url;
		$this->file = $file;
		$this->protected = $protected;
		$this->userLevel = $userLevel;
		$this->comments = $comments;
		$this->width = $width;
		$this->includePage = $includePage;
		$this->includePosition = $includePosition;
		$this->dateAdded = $dateAdded;
		$this->active = $active;
		
		// members inherited from TethyBase
		$this->indexable = true;
		$this->subscribable = false;
		$this->template = 'page_output.php';
	}
	
	/* SEARCH */
	
	// search pages
	// returns an array of Page objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'pageName ASC') {
		// if search parameters are set, validate the specific parameters
		if (is_array($params)) {
			if (isset($params['id']) && is_numeric($params['id']) && $params['id'] > 0) {
				$whereParams['id'] = $params['id'];
				$where[] = "`pageId`=:id";
			}
			if (isset($params['user']) && is_numeric($params['user']) && $params['user'] > 0) {	
				$whereParams['user'] = $params['user'];
				$where[] = "`pageUserId`=:user";
			}
			if (isset($params['url']) && $params['url'] != '') {
				$whereParams['url'] = $params['url'];
				$where[] = "`pageUrl`=:url";
			}
			if (isset($params['keyword']) && $params['keyword'] != '') {
				$whereParams['kwName'] = '%'.$params['keyword'].'%';
				$whereParams['kwDesc'] = '%'.$params['keyword'].'%';
				$whereParams['kwDetails'] = '%'.$params['keyword'].'%';
				$whereParams['kwValue'] = '%'.$params['keyword'].'%';
				
				$where[] = "(`pageTitle` LIKE :kwName OR `pageDescription` LIKE :kwDesc OR `pageKeywords` LIKE :kwDetails OR `pageValue` LIKE :kwValue)";
			}
		}
		
		// determine whether to return inactive page
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for active search-parameter
			if (is_array($params)) {
				if (isset($params['active']) && is_numeric($params['active'])) {
					$whereParams['active'] = $params['active'];
					$where[] = "`pageActive`=:active";
				}
			}
		} // else, only return active pages
		else {
			$whereParams['active'] = 1;
			$where[] = "`pageActive`=:active";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`pageId`) AS result FROM `pages`";
		$query = "SELECT `pageId` AS id, ".
				"`pageUserId` AS user, ".
				"`pageName` AS name, ".
				"`pageTitle` AS title, ".
				"`pageDescription` AS description, ".
				"`pageAuthor` AS author, ".
				"`pageKeywords` AS keywords, ".
				"`pageUrl` AS url, ".
				"`pageFile` AS file, ".
				"`pageProtected` AS protected, ".
				"`pageUserLevel` AS userLevel, ".
				"`pageComments` AS comments, ".
				"`pageWidth` AS width, ".
				"`pageIncludePage` AS includePage, ".
				"`pageIncludePosition` AS includePosition, ".
				"`pageDateCreated` AS dateAdded, ".
				"`pageActive` AS active ".
				"FROM `pages`";
		
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
			$resultArr[] = new Page($obj['id'], $obj['user'], $obj['name'], $obj['title'], $obj['description'], $obj['keywords'], $obj['author'], $obj['url'], $obj['file'], $obj['protected'], $obj['userLevel'], $obj['comments'], $obj['width'], $obj['includePage'], $obj['includePosition'], $obj['dateAdded'], $obj['active']);
		}
		
		return $resultArr;
	}
	
	// returns Page object associated to specific pageId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = Page::search(array('id' => $id));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	// returns Page object associated to specific pageUrl
	public static function getByUrl($url) {
		if ($url == '') {
			return false;
		}
		
		$tempArr = Page::search(array('url' => $url));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	/* CREATE/EDIT/DELETE */
	
	public function __get($var) {
		switch ($var) {
			case 'value':
				$ret = ($this->value != '') ? $this->value : $this->getValue();
			break;
			case 'css':
				$ret = ($this->css != '') ? $this->css : $this->getCSS();
			break;
			default:
				$ret = (isset($this->$var)) ? $this->$var : false;
			break;
		}
		
		return $ret;
	}
	
	public function __set($var, $value) {
		switch($var) {
			case 'name':
				if ($this->name != $value) {
					$ret = $this->setName($value);
				}
			break;
			case 'value':
				if ($this->value != $value) {
					$ret = $this->setValue($value);
				}
			break;
			case 'css':
				if ($this->css != $value) {
					$ret = $this->setCSS($value);
				}
			break;
			case 'title':
				if ($this->title != $value) {
					$ret = $this->setTitle($value);
				}
			break;
			case 'description':
				if ($this->description != $value) {
					$ret = $this->setDescription($value);
				}
			break;
			case 'keywords':
				if ($this->keywords != $value) {
					$ret = $this->setKeywords($value);
				}
			break;
			case 'author':
				if ($this->author != $value) {
					$ret = $this->setAuthor($value);
				}
			break;
			case 'url':
				if ($this->url != $value) {
					$ret = $this->setUrl($value);
				}
			break;
			case 'file':
				if ($this->file != $value) {
					$ret = $this->setFile($value);
				}
			break;
			case 'protected':
				if ($this->protected != $value) {
					$ret = $this->setProtected($value);
				}
			break;
			case 'userLevel':
				if ($this->userLevel != $value) {
					$ret = $this->setUserLevel($value);
				}
			break;
			case 'comments':
				if ($this->comments != $value) {
					$ret = $this->setComments($value);
				}
			break;
			case 'width':
				if ($this->width != $value) {
					$ret = $this->setWidth($value);
				}
			break;
			case 'includePage':
				if ($this->includePage != $value) {
					$ret = $this->setIincludePage($value);
				}
			break;
			case 'includePosition':
				if ($this->includePosition != $value) {
					$ret = $this->setIncludePosition($value);
				}
			break;
			case 'active':
				if ($this->active != $value) {
					$ret = $this->setActive($value);
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
	
	// creates a new page
	public static function add($name, $value = null, $css = null, $title = null, $description = null, $keywords = null, $author = null, $url = null, $file = null, $protected = 0, $userLevel = 'none', $comments = 'none', $width = 12, $includePage = null, $includePosition = null, $active = 1) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_WARNING, 'You do not have permission to add pages.');
			return false;
		}

		// validate the account information
		if ($name == '') {
			SystemMessage::save(MSG_WARNING, 'Page name cannot be null.', 'name');
			$fail[] = true;
		}
		if (!in_array($userLevel, array('none', 'user', 'admin'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid user level.', 'user_level');
			$fail[] = true;
		}
		if (!in_array($comments, array('none', 'bottom', 'left', 'right'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid comments setting.', 'comments');
			$fail[] = true;
		}
		if ($includePage > 0 && !in_array($includePosition, array('left', 'right'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid page position.', 'include_position');
			$fail[] = true;
		}
		if ($includePage > 0 && $width == 12) {
			SystemMessage::save(MSG_WARNING, 'Content too wide: unable to include a second page at this width.', 'width');
			$fail[] = true;
		}
		if ($width < 1 || $width > 12) {
			SystemMessage::save(MSG_WARNING, 'Invalid page width.', 'width');
			$fail[] = true;
		}
		if ($url != '' && !Page::isUrlValid($url)) {
			SystemMessage::save(MSG_WARNING, 'Invalid url.', 'url');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}

		if ($GLOBALS['userObj']->level == 'super-admin') {
			$protected = ($protected === 1) ? 1 : 0;
		} else {
			$protected = 0;
		}
		$active = ($active === 0) ? 0 : 1;
		
		// insert page info into db
		$query = "INSERT INTO `pages` (`pageUserId`, `pageName`, `pageValue`, `pageCSS`, `pageTitle`, `pageDescription`, `pageKeywords`, `pageAuthor`, `pageUrl`, `pageFile`, `pageProtected`, `pageUserLevel`, `pageComments`, `pageWidth`, `pageIncludePage`, `pageIncludePosition`, `pageDateUpdated`, `pageDateCreated`, `pageActive`) VALUES (:user, :name, :value, :css, :title, :description, :keywords, :author, :url, :file, :protected, :userLevel, :comments, :width, :includePage, :includePosition, :dateU, :dateC, :active)";
		
		$params = array(
			'user' => $GLOBALS['userObj']->id,
			'name' => $name,
			'value' => $value,
			'css' => $css,
			'title' => $title,
			'description' => $description,
			'keywords' => $keywords,
			'author' => $author,
			'url' => $url,
			'file' => $file,
			'protected' => $protected,
			'userLevel' => $userLevel,
			'comments' => $comments,
			'width' => $width,
			'includePage' => $includePage,
			'includePosition' => $includePosition,
			'active' => $active,
			'dateU' => date(DATE_SQL_FORMAT),
			'dateC' => date(DATE_SQL_FORMAT)
		);

		try {
			$id = $GLOBALS['dbObj']->insert($query, $params);
			
			if ($id) {
				$tempPage = Page::getById($id);
				if ($tempPage) {
					$tempPage->refresh();
				}
				return $id;
			}
			
			return false;
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error creating page.');
			return false;
		}
	}
	
	// remove a page
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		$query = "DELETE FROM `pages` WHERE `pageId`=:id";
		$params = array('id' => $this->id);
		
		try {
			$ret = $GLOBALS['dbObj']->delete($query, $params);
			$this->refresh();
			return $ret;
		} catch (Exception $e) {
			return false;
		}
	}	
	
	// update the page's name
	public function setName($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if ($val == '') {
			SystemMessage::save(MSG_WARNING, 'Page name cannot be null.', 'name');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageName`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's content
	public function setValue($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageValue`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's CSS
	public function setCSS($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageCSS`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's title
	public function setTitle($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageTitle`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's keywords
	public function setKeywords($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageKeywords`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's author
	public function setAuthor($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if ($val == '') {
			SystemMessage::save(MSG_WARNING, 'Page name cannot be null.', 'name');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageAuthor`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's description
	public function setDescription($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageDescription`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}

	// update the page's url
	public function setUrl($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if ($url != '' && !Page::isUrlValid($url)) {
			SystemMessage::save(MSG_WARNING, 'Invalid url.', 'url');
			$fail[] = true;
		}
		
		$query = "UPDATE `pages` SET `pageUrl`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's file
	public function setFile($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageFile`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's protected status
	public function setProtected($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if ($val == '') {
			SystemMessage::save(MSG_WARNING, 'Page name cannot be null.', 'name');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageName`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's user level
	public function setUserLevel($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if (!in_array($val, array('none', 'user', 'admin'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid user level.', 'user_level');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageUserLevel`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's comments
	public function setComments($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if (!in_array($val, array('none', 'bottom', 'left', 'right'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid comment setting.', 'comments');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageComments`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's width
	public function setWidth($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if ($val < 1 || $val > 12) {
			SystemMessage::save(MSG_WARNING, 'Invalid page width.', 'width');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageWidth`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's include page
	public function setIncludePage($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageIncludePage`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}
	
	// update the page's included page position
	public function setIncludePosition($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		if ($val != '' && !in_array($val, array('left', 'right'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid page position.', 'include_position');
			return false;
		}
		
		$query = "UPDATE `pages` SET `pageIncludePosition`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
			return false;
		}
	}

	// update the page's active status
	public function setActive($val = 1) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this page.');
			return false;
		}
		
		$val = ($val === 0) ? 0 : 1;
		
		$query = "UPDATE `pages` SET `pageActive`=:val WHERE `pageId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating page.');
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
			case 'setProtected':
				$ret = $GLOBALS['userObj']->level == 'super-admin';
			break;
			default:
				$ret = ($this->protected == 1) ? $GLOBALS['userObj']->level == 'super-admin' : User::isAdmin();
			break;
		}
		
		return $ret;
	}
	
	public function canView() {
		switch ($this->userLevel) {
			case 'admin':
				$ret = User::isAdmin();
			break;
			case 'user':
				$ret = User::isLoggedIn();
			break;
			case 'none':
			default:
				$ret = ($this->active) ? true : false;
			break;
		}
		
		return $ret;
	}
	
	/* TEMP PAGES */
	
	// store a temporary page in the database
	public static function saveTemp($value = null, $css = null, $title = null, $comments = 'none', $width = 12, $includePage = null, $includePosition = null) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_WARNING, 'You do not have permission to add pages.');
			return false;
		}

		if (!in_array($comments, array('none', 'bottom', 'left', 'right'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid comments setting.', 'comments');
			$fail[] = true;
		}
		if ($includePage > 0 && !in_array($includePosition, array('left', 'right'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid page position.', 'include_position');
			$fail[] = true;
		}
		if ($includePage > 0 && $width == 12) {
			SystemMessage::save(MSG_WARNING, 'Content too wide: unable to include a second page at this width.', 'width');
			$fail[] = true;
		}
		if ($width < 1 || $width > 12) {
			SystemMessage::save(MSG_WARNING, 'Invalid page width.', 'width');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		// insert page info into db
		$query = "INSERT INTO `temp_pages` (`tpValue`, `tpCSS`, `tpTitle`, `tpComments`, `tpWidth`, `tpIncludePage`, `tpIncludePosition`, `tpDateCreated`) VALUES (:value, :css, :title, :comments, :width, :includePage, :includePosition, :date)";
		
		$params = array(
			'value' => $value,
			'css' => $css,
			'title' => $title,
			'comments' => $comments,
			'width' => $width,
			'includePage' => $includePage,
			'includePosition' => $includePosition,
			'date' => date(DATE_SQL_FORMAT)
		);

		try {
			return $GLOBALS['dbObj']->insert($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error creating temp page.');
			return false;
		}
	}
	
	// retrieve temp page from the database
	public static function getTempById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$whereParams['id'] = $id;
		$where[] = "`tpId`=:id";
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$query = "SELECT `tpId` AS id, ".
				"`tpTitle` AS title, ".
				"`tpValue` AS value, ".
				"`tpCSS` AS css, ".
				"`tpComments` AS comments, ".
				"`tpWidth` AS width, ".
				"`tpIncludePage` AS includePage, ".
				"`tpIncludePosition` AS includePosition, ".
				"`tpDateCreated` AS dateAdded ".
				"FROM `temp_pages`";
		
		$query .= $whereClause . addQuerySort($sort) . addQueryLimit($perPage, $pageNum);

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('id' => $obj['id'], 'title' => $obj['title'], 'value' => $obj['value'], 'css' => $obj['css'], 'comments' => $obj['comments'], 'width' => $obj['width'], 'includePage' => $obj['includePage'], 'includePosition' => $obj['includePosition'],'dateCreated' => $obj['dateAdded']);
		}
		
		return $resultArr[0];
	}
	
	/* OUTPUT */
	
	public function getOutputParams() {
		$this->getValue(true);
		$width = ($included) ? 12 : $this->width;
		$ret = '';
		
		// associate include page to proper column
		if ($this->includePage > 0) {
			$leftCol = ($this->includePosition == 'left') ? Page::getById($this->includePage) : 'this';
			$rightCol = ($this->includePosition == 'right') ? Page::getById($this->includePage) : 'this';
			$includeWidth = 12 - $this->width;
		} else {
			$leftCol = 'this';
		}

		if ($this->comments != 'none') {
			if ($this->includePage == 0  && !$included) {
				switch($this->comments) {
					case 'left':
						$leftCol = '<div id="ccPage'.$this->id.'">'.outputDisqus().'</div>';
						$rightCol = 'this';
						$includeWidth = 12 - $this->width;
					break;
					case 'right':
						$leftCol = 'this';
						$rightCol = '<div id="ccPage'.$this->id.'">'.outputDisqus().'</div>';
						$includeWidth = 12 - $this->width;
					break;
					case 'bottom':
					default:
						$bottom = outputDisqus();
					break;
				}
			} else {
				$bottom = outputDisqus();
			}
		}
	
		$params = array(
			'width' => $width,
			'includeWidth' => $includeWidth,
			'content' => $this->value,
			'leftCol' => $leftCol,
			'rightCol' => $rightCol,
			'bottom' => $bottom
		);
		
		return $params;
	}
	
	public function outputSearchDetails($url = null) {
		// there is no page search, so this function is never used
		return;
	}
	
	public static function getSitemapParams() {
		$query = "SELECT `pageId` AS id, `pageUrl` AS url, `pageDateUpdated` AS date FROM `pages` WHERE `pageActive`=1 AND (`pageURL` IS NOT NULL AND `pageUrl`!='')";
		
		// execute the query
		$results = $GLOBALS['dbObj']->select($query);

		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('group' => 'pages', 'url' => CORE_DOMAIN.$obj['url'], 'lastmod' => $obj['date'], 'frequency' => 'monthly', 'priority' => 0.5);
		}
		
		return $resultArr;
	}
	
	/* STATS */
	
	// returns number of page views
	public static function getNumViews($url, $altUrl = null) {
		if ($url == '') {
			return false;
		}
		
		$params = array('url' => $url);
		$query = "SELECT COUNT(`pvId`) AS result FROM `page_views` WHERE `pvPage`=:url";
		
		if ($altUrl) {
			$query .= " OR `pvPage`=:alt";
			$params['alt'] = $altUrl;
		}
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	// returns date the page was viewed last
	public static function getLastViewed($url, $altUrl = null) {
		if ($url == '') {
			return false;
		}
		
		$params = array('url' => $url);
		$query = "SELECT `pvDate` AS result FROM `page_views` WHERE `pvPage`=:url";
		
		if ($altUrl) {
			$query .= " OR `pvPage`=:alt";
			$params['alt'] = $altUrl;
		}
		
		$query .= addQuerySort('pvDate DESC') . addQueryLimit(1);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	// calcuates average load time (based off benchmarking data) for the page
	// if benchmarking is set to 0, no data will be available
	// if benchmarking is set to 1, load times will be when the script finishes executing
	// if benchmarking is set to 2, loads times will be when the page load event fires
	public static function getAverageLoadTime($url, $altUrl = null) {
		if (CORE_BENCHMARK_LEVEL < 1) {
			return 0;
		}
		
		if ($url == '') {
			return false;
		}
		
		if ($altUrl) {
			$params['alt'] = $altUrl;
			$params['url'] = $url;
			$where[] = "(`bmPage`=:url OR `bmPage`=:alt)";
		} else {
			$params['url'] = $url;
			$where[] = "`bmPage`=:url";
		}
		
		switch(CORE_BENCHMARK_LEVEL) {
			case 2:
				$params['action'] = 'Page Render';
			break;
			case 1:
			default:
				$params['action'] = 'Script end';
			break;
		}
		
		$where[] = "`bmAction`=:action";
		
		$whereClause = addQueryWhere($where);

		$query = "SELECT AVG(`bmExecTime`) AS result FROM `benchmarking`" . $whereClause;

		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	/* UTILITIES */
	
	public static function getGridClass($width) {
		if (!is_numeric($width) || $width < 1 || $width > 12) {
			$width = 12;
		}
		
		switch ($width) {
			case 1:
				$ret = 'onecol';
			break;
			case 2:
				$ret = 'twocol';
			break;
			case 3:
				$ret = 'threecol';
			break;
			case 4:
				$ret = 'fourcol';
			break;
			case 5:
				$ret = 'fivecol';
			break;
			case 6:
				$ret = 'sixcol';
			break;
			case 7:
				$ret = 'sevencol';
			break;
			case 8:
				$ret = 'eightcol';
			break;
			case 9:
				$ret = 'ninecol';
			break;
			case 10:
				$ret = 'tencol';
			break;
			case 11:
				$ret = 'elevencol';
			break;
			case 12:
			default;
				$ret = 'twelvecol';
			break;
		}
		
		return $ret;
	}
	
	private function getValue($forceRetrieve = false) {
		if ($this->value != '') {
			$ret = $this->value;
		}
		
		if ($forceRetrieve || $ret == '') {
			$query = "SELECT `pageValue` AS result FROM `pages` WHERE `pageId`=:id";
			
			try {
				$ret = $GLOBALS['dbObj']->fetchResult($query, array('id' => $this->id));
				
				$this->value = $ret;
			} catch(Exception $e) {
				return false;
			}
		}

		return $ret;
	}
	
	private function getCSS($forceRetrieve = false) {
		if ($this->css != '') {
			$ret = $this->css;
		}
		
		if ($forceRetrieve || $ret == '') {
			$query = "SELECT `pageCSS` AS result FROM `pages` WHERE `pageId`=:id";
			
			try {
				$ret = $GLOBALS['dbObj']->fetchResult($query, array('id' => $this->id));
				
				$this->css = $ret;
			} catch(Exception $e) {
				return false;
			}
		}

		return $ret;
	}
	
	public static function isUrlValid($url) {
		if ($url == '') {
			return false;
		}
		
		$prohibited = array('homepage', 'index', 'projects', 'blog', 'portfolio');
		
		if (in_array($url, $prohibited)) {
			return false;
		}
		
		// check is valid url
		$pattern = '/^[a-zA-Z0-9_\-]{3,30}$/i';
		if (!preg_match($pattern, $url)) {
			return false;
		}
		
		// check that the url is not already used by another page
		$query = "SELECT `pageId` AS result FROM `pages` WHERE UPPER(`pageUrl`)=:url";
		$params = array('url' => strtoupper($url));
		
		try {
			return !$GLOBALS['dbObj']->fetchResult($query, $params);
		} catch(Exception $e) {
			return false;
		}
		
		return false;
	}
	
	public static function getNameById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$query = "SELECT `pageName` AS result FROM `pages` WHERE `pageId`=:id";
		$params = array('id' => $id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	public static function getList($sort = 'pageName ASC', $showInactive = false) {
		// determine whether to return inactive page
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for active search-parameter
			if (!$showInactive) {
				$whereParams['active'] = 1;
				$where[] = "`pageActive`=:active";
			}
		} // else, only return active pages
		else {
			$whereParams['active'] = 1;
			$where[] = "`pageActive`=:active";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$query = "SELECT `pageId` AS id, ".
				"`pageName` AS name, ".
				"`pageUrl` AS url, ".
				"`pageActive` AS active ".
				"FROM `pages`";
		
		$query .= $whereClause . addQuerySort($sort);

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('id' => $obj['id'], 'name' => $obj['name'], 'url' => $obj['url'], 'active' => $obj['active']);
		}
		
		return $resultArr;
	}
};

?>