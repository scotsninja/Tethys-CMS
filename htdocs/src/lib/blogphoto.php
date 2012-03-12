<?php
/**
 * Administer photos for a photo blog
 *
 * @category   Blog
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.9.0
 * @since      Class available since Release 0.9.0
 */

class BlogPhoto extends Outputtable implements iTethysBase {

	/* MEMBERS */
	
	private $id;
	private $blog;
	private $order;
	private $title;
	private $caption;
	private $file;
	private $datePosted;
	private $dateAdded;
	
	private $tags;
	private $tagArr;
	
	/* METHODS */
	
	public function __construct($id = 0, $blog = null, $order = null, $title = null, $caption = null, $file = null, $datePosted = null, $dateAdded = null) {
		$this->id = $id;
		$this->blog = $blog;
		$this->order = $order;
		$this->title = $title;
		$this->caption = $caption;
		$this->file = $file;
		$this->datePosted = $datePosted;
		$this->dateAdded = $dateAdded;
		
		$this->tagArr = $this->getTags();
		$this->tags = (is_array($this->tagArr)) ? implode(',', $this->tagArr) : null;
	}
	
	/* SEARCH */
	
	// search blog photos
	// returns an array of BlogPhoto objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'bpDatePosted DESC') {
		// if search parameters are set, validate the specific parameters
		if (is_array($params)) {
			if (isset($params['id']) && is_numeric($params['id']) && $params['id'] > 0) {
				$whereParams['id'] = $params['id'];
				$where[] = "`bpId`=:id";
			}
			if (isset($params['blog']) && is_numeric($params['blog']) && $params['blog'] > 0) {
				$whereParams['blog'] = $params['blog'];
				$where[] = "`bpBlogId`=:blog";
			}
			if (isset($params['tag']) && $params['tag'] != '') {
				$whereParams['tag'] = trim($params['tag']);
				$where[] = "`bptValue`=:tag";
				$joinTables[] = 'blog_photo_tags';
				$groupBy = ' GROUP BY `bpId`';
			}
			if (isset($params['year']) && is_numeric($params['year']) && $params['year'] > 0) {
				$whereParams['year'] = $params['year'];
				$where[] = "YEAR(`bpDatePosted`)=:year";
			}
			if (isset($params['month']) && is_numeric($params['month']) && $params['month'] > 0) {
				$whereParams['month'] = $params['month'];
				$where[] = "MONTH(`bpDatePosted`)=:month";
			}
			if (isset($params['day']) && is_numeric($params['day']) && $params['day'] > 0) {
				$whereParams['day'] = $params['day'];
				$where[] = "DAY(`bpDatePosted`)=:day";
			}
			if (isset($params['keyword']) && $params['keyword'] != '') {
				$whereParams['kwName'] = '%'.$params['keyword'].'%';
				$whereParams['kwDesc'] = '%'.$params['keyword'].'%';
				$whereParams['kwTag'] = trim($params['keyword']);
				
				$where[] = "(`bpTitle` LIKE :kwName OR `bpCaption` LIKE :kwDesc OR `bptValue`=:kwTag)";
				$joinTables[] = 'blog_photo_tags';
				$groupBy = ' GROUP BY `bpId`';
			}
		}
		
		// determine whether to return future photos
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for future search-parameter
			if (is_array($params)) {
				if (!(isset($params['future']) && $params['future'])) {
					$where[] = "`bpDatePosted`<=NOW()";
				}
			}
		} // else, only return active comments
		else {
			$where[] = "`bpDatePosted`<=NOW()";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`bpId`) AS result FROM `blog_photos`";
		$query = "SELECT `bpId` AS id, ".
				"`bpBlogId` AS blog, ".
				"`bpTitle` AS title, ".
				"`bpCaption` AS caption, ".
				"`bpFile` AS file, ".
				"`bpOrder` AS pOrder, ".
				"`bpDatePosted` AS datePosted, ".
				"`bpDateAdded` AS dateAdded ".
				"FROM `blog_photos`";
				
		if (is_array($joinTables)) {
			if (in_array('blog_photo_tags', $joinTables)) {
				$query .= " LEFT JOIN `blog_photo_tags` ON `bptId`=`bpId`";
				$totQ .= " LEFT JOIN `blog_photo_tags` ON `bptId`=`bpId`";
			}
		}
		
		$query .= $whereClause . $groupBy . addQuerySort($sort) . addQueryLimit($perPage, $pageNum);
		$totQ .= $whereClause;

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			$totalResults = 0;
			return false;
		}

		$totalResults = $GLOBALS['dbObj']->fetchResult($totQ, $whereParams);

		foreach ($results as $obj) {
			$resultArr[] = new BlogPhoto($obj['id'], $obj['blog'], $obj['pOrder'], $obj['title'], $obj['caption'], $obj['file'], $obj['datePosted'], $obj['dateAdded']);
		}
		
		return $resultArr;
	}
	
	// returns BlogPhoto object associated to specific bpId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = BlogPhoto::search(array('id' => $id, 'future' => true));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	/* CREATE/EDIT/DELETE */
	
	public function __get($var) {
		switch ($var) {
			case 'imagePath':
				$ret = $this->getImagePath();
			break;
			case 'fullUrl':
				$ret = $this->getFullUrl();
			break;
			default:
				$ret = (isset($this->$var)) ? $this->$var : false;
			break;
		}
		
		return $ret;
	}
	
	public function __set($var, $value) {
		switch($var) {
			case 'title':
				if ($this->title != $value) {
					$ret = $this->setTitle($value);
				}
			break;
			case 'tags':
				if ($this->tags != $value) {
					$ret = $this->setTags($value);
				}
			break;
			case 'caption':
				if ($this->excerpt != $value) {
					$ret = $this->setCaption($value);
				}
			break;
			case 'datePosted':
				if ($this->datePosted != $value) {
					$ret = $this->setDatePosted($value);
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
	
	// creates a new blog photo
	public static function add($blog, $order, $file, $title = null, $tags = null, $caption = null, $datePosted = null) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_WARNING, 'You do not have permission to add blog photos.');
			return false;
		}

		// validate the account information
		if (!is_numeric($blog) || $blog < 1) {
			SystemMessage::save(MSG_WARNING, 'Invalid blog.');
			$fail[] = true;
		}
		if ($file == '') {
			SystemMessage::save(MSG_WARNING, 'Unknown file.');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		if (!is_numeric($order) || $order < 1) {
			$order = 9999999;
		}
		if ($datePosted == '') {
			$datePosted = date(DATE_SQL_FORMAT);
		}
		
		// insert blog info into db
		$query = "INSERT INTO `blog_photos` (`bpBlogId`, `bpOrder`, `bpTitle`, `bpCaption`, `bpFile`, `bpDatePosted`, `bpDateAdded`) VALUES (:blog, :order, :title, :caption, :file, :dtPost, :dtAdd)";
		
		$params = array(
			'blog' => $blog,
			'order' => $order,
			'title' => $title,
			'caption' => $caption,
			'file' => $file,
			'dtPost' => $datePosted,
			'dtAdd' => date(DATE_SQL_FORMAT)
		);
		
		try {
			$id = $GLOBALS['dbObj']->insert($query, $params);
			
			if ($id) {
				$tempPhoto = BlogPhoto::getById($id);

				if ($tempPhoto && $tempPhoto->setTags($tags)) {
					$tempPhoto->reSort();
					return true;
				} else {
					$GLOBALS['dbObj']->delete("DELETE FROM `blog_photos` WHERE `bpId`=:id", array('id' => $id));
					return false;
				}
			}
			
			return false;
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error saving blog photo.');
			return false;
		}
	}
	
	// remove a blog photo
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		$query = "DELETE FROM `blog_photos` WHERE `bpId`=:id";
		$params = array('id' => $this->id);
		
		$tempImage = $this->imagePath;
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			if ($GLOBALS['dbObj']->delete($query, $params)) {
				// try to remove photo tags
				$GLOBALS['dbObj']->delete("DELETE FROM `blog_photo_tags` WHERE `bptId`=:id", array('id' => $id));
				
				// update image ordering
				if ($this->reSort()) {
					// try to remove file from server
					if ($tempImage) {
						@unlink(CORE_DIR_DEPTH.$tempImage);
					}
					
					$GLOBALS['dbObj']->commit();
					return true;
				}
			}
			
			$GLOBALS['dbObj']->rollBack();
			return false;
		} catch (Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			return false;
		}
	}	
	
	// update the blog photos's title
	public function setTitle($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog photo.');
			return false;
		}
		
		$query = "UPDATE `blog_photos` SET `bpTitle`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog photo.');
			return false;
		}
	}
	
	// update the blog photos's tags
	public function setTags($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog photo.');
			return false;
		}
		
		// strip extra whitespace between commas
		if ($val != '') {
			$tempArr = explode(',', $val);
			$totalTags = count($tempArr);
		} else { 
			$totalTags = 0;
		}
		
		$query = "INSERT INTO `blog_photo_tags` (`bptId`, `bptValue`) VALUES ";
		
		if (is_array($tempArr)) {
			for($i = 0; $i < $totalTags; $i++) {
				$index = 'tag'.$i;
				$query .= "(".$this->id.",:".$index.")";
				$params[$index] = trim($tempArr[$i]);
				
				if ($i < ($totalTags-1)) {
					$query .= ',';
				}
			}
		}
		
		$GLOBALS['dbObj']->beginTransaction();
		
		try {
			// remove old tags
			$this->clearTags();
			
			if ($totalTags > 0) {
				if ($GLOBALS['dbObj']->insert($query, $params)) {
					$GLOBALS['dbObj']->commit();
					return true;
				}

				$GLOBALS['dbObj']->rollBack();
				return false;
			}

			$GLOBALS['dbObj']->commit();
			return true;
		} catch(Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			SystemMessage::save(MSG_ERROR, 'Error updating blog photo.');
			return false;
		}
	}
	
	private function clearTags() {
		$query = "DELETE FROM `blog_photo_tags` WHERE `bptId`=:id";
		$params = array('id' => $this->id);
		
		try {
			return $GLOBALS['dbObj']->delete($query, $params);
		} catch(Exception $e) {
			return false;
		}
	}
	
	// update the blog photo's caption
	public function setCaption($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog photo.');
			return false;
		}
		
		$query = "UPDATE `blog_photos` SET `bpCaption`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog photo.');
			return false;
		}
	}
	
	// update the blog photo's post date
	public function setDatePosted($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog photo.');
			return false;
		}
		
		if ($val == '') {
			$val = date(DATE_SQL_FORMAT);
		}
		
		$query = "UPDATE `blog_photos` SET `bpDatePosted`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog photo.');
			return false;
		}
	}
	
	/* PERMISSIONS */
	
	// returns true if active user has permission to perform passed function
	public function canEdit($function = null) {
		if (!User::isLoggedIn()) {
			return false;
		}
		
		return User::isAdmin();
	}
	
	/* IMAGES */
	
	/* UTILITIES */
	
	public function getImagePath() {	
		return '/img/blogs/'.$this->blog.'/'.$this->file;
	}
	
	private function getTags($forceRetrieve = false) {
		if ($this->tags != '') {
			$ret = $this->tags;
		}
		
		if ($forceRetrieve || $ret == '') {
			$query = "SELECT `bptValue` FROM `blog_photo_tags` WHERE `bptId`=:id" . addQuerySort('bptValue');
			
			$results = $GLOBALS['dbObj']->select($query, array('id' => $this->id));
			
			if ($results) {
				foreach ($results as $tag) {
					$ret[] = $tag['bptValue'];
				}
			}
		}
		return $ret;
	}
	
	// @todo
	public function getFullUrl() {
		$ret = '/blogs/';
		
		$tempBlog = Blog::getById($this->blog);
		
		if (!$tempBlog) {
			return $ret;
		}
		
		if ($tempBlog->url != '') {
			$ret .= $tempBlog->url.'/';
			$ret .= ($this->url != '') ? $this->url : $this->id;
		} else {
			$ret .= $tempBlog->id.'&post=';
			$ret .= ($this->url != '') ? $this->url : $this->id;
		}

		return $ret;
	}
	
	// eliminates gaps in the order numbers and ensures each number is unique (per blog)
	public function reSort() {
		$query = "UPDATE `blog_photos` SET `bpOrder`=(SELECT @rownum:=@rownum+1 rownum FROM (SELECT @rownum:=0) r) WHERE `bpBlogId`=:id" . addQuerySort('bpOrder');
		$params = array('id' => $this->blog);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			return false;
		}
	}
	
	public static function getPictureOfTheDay() {
		// build the search query
		$query = "SELECT `bpId` AS id, ".
				"`bpBlogId` AS blog, ".
				"`bpTitle` AS title, ".
				"`bpCaption` AS caption, ".
				"`bpFile` AS file, ".
				"`bpOrder` AS pOrder, ".
				"`bpDatePosted` AS datePosted, ".
				"`bpDateAdded` AS dateAdded ".
				"FROM `daily_picture` LEFT JOIN `blog_photos` ON `dpPhotoId`=`bpId`".
				"WHERE `dpDate`=:date";
		
		$params = array('date' => date('Y-m-d'));
		
		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $params);
		
		if ($results) {
			foreach ($results as $obj) {
				$resultArr[] = new BlogPhoto($obj['id'], $obj['blog'], $obj['pOrder'], $obj['title'], $obj['caption'], $obj['file'], $obj['datePosted'], $obj['dateAdded']);
			}
			
			return $resultArr[0];
		}

		// get random photo
		$query = "SELECT `bpId` AS id, ".
				"`bpBlogId` AS blog, ".
				"`bpTitle` AS title, ".
				"`bpCaption` AS caption, ".
				"`bpFile` AS file, ".
				"`bpOrder` AS pOrder, ".
				"`bpDatePosted` AS datePosted, ".
				"`bpDateAdded` AS dateAdded ".
				"FROM `blog_photos` WHERE `bpDatePosted`<=NOW()" . addQuerySort('RAND()') . addQueryLimit(1);
		
		$results = $GLOBALS['dbObj']->select($query);
		
		if ($results) {
			foreach ($results as $obj) {
				$resultArr[] = new BlogPhoto($obj['id'], $obj['blog'], $obj['pOrder'], $obj['title'], $obj['caption'], $obj['file'], $obj['datePosted'], $obj['dateAdded']);
			}
			
			$query = "INSERT INTO `daily_picture` VALUES (:id, :date)";
			$params = array('id' => $resultArr[0]->id, 'date' => date('Y-m-d'));
			
			$GLOBALS['dbObj']->insert($query, $params);
			return $resultArr[0];
		}
		
		return false;
	}
	
	// @todo
	public static function getList($sort = 'bpDatePosted ASC', $showInactive = false) {}
	public static function getNameById($id) {}
	public function getOutputParams() {}
	public function outputSearchDetails($url) {}
	public function canView() {
		return false;
	}
	public static function getSitemapParams() {}
};

?>