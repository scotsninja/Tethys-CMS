<?php
/**
 * Administer posts for a blog
 *
 * @category   Blog
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.6.0
 * @since      Class available since Release 0.6.0
 */

class BlogPost extends Outputtable implements iTethysBase {

	/* PROPERTIES */
	
	private $id;
	private $blog;
	private $title;
	private $excerpt;
	private $comments;
	private $url;
	private $image;
	private $datePosted;
	private $dateAdded;
	
	private $value;
	private $tags;
	private $tagArr;
	
	/* METHODS */
	
	public function __construct($id = 0, $blog = null, $title = null, $excerpt = null, $comments = 'closed', $url = null, $image = null, $datePosted = null, $dateAdded = 0) {
		$this->id = $id;
		$this->blog = $blog;
		$this->title = $title;
		$this->excerpt = $excerpt;
		$this->comments = $comments;
		$this->url = $url;
		$this->image = $image;
		$this->datePosted = $datePosted;
		$this->dateAdded = $dateAdded;

		$this->tagArr = $this->getTags();
		$this->tags = (is_array($this->tagArr)) ? implode(',', $this->tagArr) : null;
		
		// members inherited from TethyBase
		$this->indexable = true;
		$this->subscribable = false;
		$this->template = 'blog_post_single.php';
	}
	
	/* SEARCH */
	
	// search blog posts
	// returns an array of BlogPost objects
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
				$joinTables[] = 'blog_post_tags';
				$groupBy = ' GROUP BY `bpId`';
			}
			if (isset($params['url']) && $params['url'] != '') {
				$whereParams['url'] = $params['url'];
				$where[] = "`bpUrl`=:url";
			}
			if (isset($params['title']) && $params['title'] != '') {
				$whereParams['title'] = '%'.$params['title'].'%';
				$where[] = "`bpTitle` LIKE :title";
			}
			if (isset($params['year']) && is_numeric($params['year']) && $params['year'] > 0) {
				$whereParams['year'] = $params['year'];
				$where[] = "YEAR(`bpDatePosted`)=:year";
			}
			if (isset($params['month']) && is_numeric($params['month']) && $params['month'] > 0) {
				$whereParams['month'] = $params['month'];
				$where[] = "MONTH(`bpDatePosted`)=:month";
			}
			if (isset($params['keyword']) && $params['keyword'] != '') {
				$whereParams['kwName'] = '%'.$params['keyword'].'%';
				$whereParams['kwDesc'] = '%'.$params['keyword'].'%';
				$whereParams['kwTag'] = trim($params['keyword']);
				
				$where[] = "(`bpTitle` LIKE :kwName OR `bpValue` LIKE :kwDesc OR `bptValue`=:kwTag)";
				$joinTables[] = 'blog_post_tags';
				$groupBy = ' GROUP BY `bpId`';
			}
		}
		
		// determine whether to return future posts
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for future search-parameter
			if (is_array($params)) {
				if (isset($params['future']) && !$params['future']) {
					$whereParams['hours'] = 2;
					$where[] = "`bpDatePosted`<=DATE_ADD(NOW(), INTERVAL :hours HOUR)";
				}
			}
		} // else, only return active comments
		else {
			$whereParams['hours'] = 2;
			$where[] = "`bpDatePosted`<=DATE_ADD(NOW(), INTERVAL :hours HOUR)";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`bpId`) AS result FROM `blog_posts`";
		$query = "SELECT `bpId` AS id, ".
				"`bpBlogId` AS blog, ".
				"`bpTitle` AS title, ".
				"`bpExcerpt` AS excerpt, ".
				"`bpComments` AS comments, ".
				"`bpUrl` AS url, ".
				"`bpImage` AS image, ".
				"`bpDatePosted` AS datePosted, ".
				"`bpDateCreated` AS dateAdded ".
				"FROM `blog_posts`";
				
		if (is_array($joinTables)) {
			if (in_array('blog_post_tags', $joinTables)) {
				$query .= " LEFT JOIN `blog_post_tags` ON `bptId`=`bpId`";
				$totQ .= " LEFT JOIN `blog_post_tags` ON `bptId`=`bpId`";
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
			$resultArr[] = new BlogPost($obj['id'], $obj['blog'], $obj['title'], $obj['excerpt'], $obj['comments'], $obj['url'], $obj['image'], $obj['datePosted'], $obj['dateAdded']);
		}
		
		return $resultArr;
	}
	
	// returns BlogPost object associated to specific bpId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = BlogPost::search(array('id' => $id, 'future' => true));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	// returns BlogPost object associated to bpUrl
	public static function getByUrl($url) {
		if ($url == '') {
			return false;
		}
		
		$tempArr = BlogPost::search(array('url' => $url));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	/* CREATE/EDIT/DELETE */
	
	public function __get($var) {
		switch ($var) {
			case 'imagePath':
				$ret = $this->getImagePath();
			break;
			case 'value':
				$ret = ($this->value != '') ? $this->value : $this->getValue();
			break;
			case 'blurb':
				$ret = $this->getBlurb(500);
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
			case 'excerpt':
				if ($this->excerpt != $value) {
					$ret = $this->setExcerpt($value);
				}
			break;
			case 'comments':
				if ($this->comments != $value) {
					$ret = $this->setComments($value);
				}
			break;
			case 'url':
				if ($this->url != $value) {
					$ret = $this->setUrl($value);
				}
			break;
			case 'value':
				if ($this->value != $value) {
					$ret = $this->setValue($value);
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
	
	// creates a new blog post
	public static function add($blog, $value, $title = null, $tags = null, $comments = 'open-validate', $excerpt = null, $url = null, $datePosted = null) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_WARNING, 'You do not have permission to add blogs.');
			return false;
		}

		// validate the account information
		if (!is_numeric($blog) || $blog < 1) {
			SystemMessage::save(MSG_WARNING, 'Invalid blog.');
			$fail[] = true;
		}
		if ($value == '') {
			SystemMessage::save(MSG_WARNING, 'You must enter text for this post.', 'value');
			$fail[] = true;
		}
		if (!in_array($comments, array('open', 'closed', 'registered', 'open-validate'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid comment type.', 'comments');
			$fail[] = true;
		}
		if ($url != '' && !Blog::isUrlAvailable($url, 'post')) {
			SystemMessage::save(MSG_WARNING, 'This url is not available.', 'url');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}
		
		if ($datePosted == '') {
			$datePosted = 'now';
		}
		
		// insert blog info into db
		$query = "INSERT INTO `blog_posts` (`bpBlogId`, `bpTitle`, `bpExcerpt`, `bpUrl`, `bpValue`, `bpComments`, `bpDatePosted`, `bpDateCreated`) VALUES (:blog, :title, :excerpt, :url, :value, :comments, :dtPost, :dtAdd)";
		
		$params = array(
			'blog' => $blog,
			'title' => $title,
			'excerpt' => $excerpt,
			'url' => $url,
			'value' => $value,
			'comments' => $comments,
			'dtPost' => $GLOBALS['dtObj']->format($datePosted, DATE_SQL_FORMAT),
			'dtAdd' => $GLOBALS['dtObj']->format('now', DATE_SQL_FORMAT)
		);
		
		try {
			$id = $GLOBALS['dbObj']->insert($query, $params);
			
			if ($id) {
				$tempPost = BlogPost::getById($id);
				
				if ($tempPost && $tempPost->setTags($tags)) {
					return true;
				} else {
					$GLOBALS['dbObj']->delete("DELETE FROM `blog_posts` WHERE `bpId`=:id", array('id' => $id));
					return false;
				}
			}
			
			return false;
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error creating blog post.');
			return false;
		}
	}
	
	// remove a blog post
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		$query = "DELETE FROM `blog_posts` WHERE `bpId`=:id";
		$params = array('id' => $this->id);
		
		try {
			if ($GLOBALS['dbObj']->delete($query, $params)) {
				// try to remove post tags
				$GLOBALS['dbObj']->delete("DELETE FROM `blog_post_tags` WHERE `bptId`=:id", array('id' => $id));
				return true;
			}
			
			return false;
		} catch (Exception $e) {
			return false;
		}
	}	
	
	// update the blog posts's title
	public function setTitle($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog post.');
			return false;
		}
		
		$query = "UPDATE `blog_posts` SET `bpTitle`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
			return false;
		}
	}
	
	// update the blog posts's tags
	public function setTags($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog post.');
			return false;
		}
		
		// strip extra whitespace between commas
		$tempArr = explode(',', $val);
		$totalTags = count($tempArr);
		
		$query = "INSERT INTO `blog_post_tags` (`bptId`, `bptValue`) VALUES ";
		
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
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
			return false;
		}
	}
	
	// removes all associated tags from db
	private function clearTags() {
		$query = "DELETE FROM `blog_post_tags` WHERE `bptId`=:id";
		$params = array('id' => $this->id);
		
		try {
			return $GLOBALS['dbObj']->delete($query, $params);
		} catch(Exception $e) {
			return false;
		}
	}
	
	// update the blog posts's excerpt
	public function setExcerpt($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog post.');
			return false;
		}
		
		$query = "UPDATE `blog_posts` SET `bpExcerpt`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
			return false;
		}
	}
	
	// update the blog posts's comments
	public function setComments($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog post.');
			return false;
		}
		
		if ($val != 'open') {
			$val = 'closed';
		}
		
		$query = "UPDATE `blog_posts` SET `bpComments`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
			return false;
		}
	}

	// update the blog's url
	public function setUrl($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		if ($url != '' && !Blog::isUrlAvailable($url, 'post')) {
			SystemMessage::save(MSG_WARNING, 'This url is not available.', 'url');
			return false;
		}
		
		$query = "UPDATE `blog_posts` SET `bpUrl`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
			return false;
		}
	}
	
	// update the blog posts's value
	public function setValue($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog post.');
			return false;
		}
		
		if ($val == '') {
			return false;
		}
		
		$query = "UPDATE `blog_posts` SET `bpValue`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
			return false;
		}
	}
	
	// update the blog posts's post date
	public function setDatePosted($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog post.');
			return false;
		}
		
		if ($val = '') {
			$val = 'now';
		}
		
		$query = "UPDATE `blog_posts` SET `bpDatePosted`=:val WHERE `bpId`=:id";
		$params = array('id' => $this->id,
			'val' => $GLOBALS['dtObj']->format($val, DATE_SQL_FORMAT)
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog post.');
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
	
	public function canView() {
		return ($GLOBALS['dtObj']->comp('now', $this->datePosted) < 0) ? User::isAdmin() : true;
	}
	
	/* OUTPUT */
	
	public function getOutputParams() {
		$tempBlog = Blog::getById($this->blog);
		$this->getValue(true);
		
		$params = array(
			'obj' => $this,
			'name' => $tempBlog->name,
			'icon' => $tempBlog->imagePath,
			'baseUrl' => $tempBlog->fullUrl,
			'description' => $tempBlog->description,
			'postTitle' => $this->title,
			'postUrl' => $this->url,
			'fullUrl' => $this->fullUrl,
			'postDate' => $this->datePosted,
			'postComments' => $this->comments,
			'postValue' => $this->value,
			'postTags' => $this->tags,
			'tags' => $tempBlog->getTagsArr(),
			'archives' => $tempBlog->getArchiveArr(),
			'rss' => CORE_RSS_DIR.$tempBlog->getRssFile()
		);

		return $params;
	}
	
	public function outputSearchDetails($url = null) {
		return;
	}
	
	public static function getSitemapParams() {
		return;
	}
	
	/* IMAGES */
	
	// @todo
	public function setImage($file) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if ($file == '') {
			return false;
		}
		
		// if there is already an image associated to the blog, remove it
		if ($this->image != '') {
			$this->removeImage();
		}
		
		// update row in db
		$query = "UPDATE `blog_posts` SET `blogImage`=:image WHERE `bpId`=:id";
		$params = array(
			'id' => $this->id,
			'image' => $file
		);
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			if ($GLOBALS['dbObj']->insert($query, $params)) {
				// move file to blogs directory
				$uploadPath = CORE_DIR_DEPTH.'uploads/'.$file;
				
				// make sure the new path exists
				$blogPath = CORE_DIR_DEPTH.'img/blogs/';
	
				if (!file_exists($blogPath)) {
					@mkdir($blogPath);
				}
	
				$blogPath .= $this->id.'/';
				
				if (!file_exists($blogPath)) {
					@mkdir($blogPath);
				}
	
				if (file_exists($blogPath.$file)) {
					$fileNameBase = explode('.', $file);
					$s = 1;
					
					while (file_exists($blogPath.$file)) {
						$file = $fileNameBase[0] .'_'.$s++ . '.'. $fileNameBase[1];
					}
				}
				
				if (rename($uploadPath, $blogPath.$file)) {
					$GLOBALS['dbObj']->commit();
					return true;
				} else {
					SystemMessage::log(MSG_ERROR, 'Error copying blog image: '.$file);
					@unlink($uploadPath);
				}
			}

			$GLOBALS['dbObj']->rollBack();
			return false;
		} catch(Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			return false;
		}
	}
	
	public function removeImage() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if ($this->image == '') {
			return false;
		}
		
		$tempImage = $this->image;
		
		// delete image
		$query = "UPDATE `blog_posts` SET `blogImage`=:image WHERE `bpId`=:id";
		$params = array(
			'id' => $this->id,
			'image' => null
		);
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			if ($GLOBALS['dbObj']->update($query, $params)) {
				// remove file from server
				if ($tempImage) {
					@unlink(CORE_DIR_DEPTH.'img/blogs/'.$this->id.'/'.$tempImage);
				}
				
				$GLOBALS['dbObj']->commit();
				return true;
			}
			
			$GLOBALS['dbObj']->rollBack();
			return false;
		} catch(Exception $e) {
			$GLOBALS['dbObj']->rollBack();
			return false;
		}
	}
	
	/* UTILITIES */
	
	public function getImagePath() {	
		if ($this->image != '') {
			$ret = '/img/blogs/'.$this->id.'/'.$this->image;
		} else {
			$ret = '/img/skin/no_img_available.png';
		}

		return $ret;
	}
	
	private function getTags($forceRetrieve = false) {
		if ($this->tags != '') {
			$ret = $this->tags;
		}
		
		if ($forceRetrieve || $ret == '') {
			$query = "SELECT `bptValue` FROM `blog_post_tags` WHERE `bptId`=:id" . addQuerySort('bptValue');
			
			$results = $GLOBALS['dbObj']->select($query, array('id' => $this->id));
			
			if ($results) {
				foreach ($results as $tag) {
					$ret[] = $tag['bptValue'];
				}
			}
		}
		return $ret;
	}
	
	private function getValue($forceRetrieve = false) {
		if ($this->value != '') {
			$ret = $this->value;
		}
		
		if ($forceRetrieve || $ret == '') {
			$query = "SELECT `bpValue` AS result FROM `blog_posts` WHERE `bpId`=:id";
			
			try {
				$ret = $GLOBALS['dbObj']->fetchResult($query, array('id' => $this->id));
				
				$this->value = $ret;
			} catch(Exception $e) {
				return false;
			}
		}

		return $ret;
	}
	
	public function getBlurb($limit = 500, $includeReadMore = true) {
		if ($limit < 1) {
			$limit = 500;
		}
		
		$val = ($this->excerpt != '') ? $this->excerpt : strip_tags($this->getValue());
		
		$ret =  substr($val, 0, $limit);
		
		if (strlen($val) > strlen($ret)) {
			$ret .= '...';
		}

		if ($includeReadMore) {
			$ret .= ' <a href="'.$this->fullUrl.'" class="readMore">[Read More]</a>';
		}
		
		return $ret;
	}
	
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
	
	public static function getList($sort = 'bpDatePosted ASC', $showInactive = false) {
		// determine whether to return inactive page
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for active search-parameter
			if (!$showInactive) {
				$where[] = "`bpDatePosted`<=NOW()";
			}
		} // else, only return active pages
		else {
			$where[] = "`bpDatePosted`<=NOW()";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$query = "SELECT `bpId` AS id, ".
				"`bpBlogId` AS blog, ".
				"`bpTitle` AS title, ".
				"`bpDatePosted` AS date ".
				"FROM `blog_posts`";
		
		$query .= $whereClause . addQuerySort($sort);

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('id' => $obj['id'], 'blog' => $obj['blog'], 'title' => $obj['title'], 'date-posted' => $obj['date']);
		}
		
		return $resultArr;
	}
	
	public static function getNameById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$query = "SELECT `bpTitle` AS result FROM `blog_posts` WHERE `bpId`=:id";
		$params = array('id' => $id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
};

?>