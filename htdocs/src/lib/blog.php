<?php
/**
 * Administer blogs
 *
 * @category   Blog
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.6.0
 * @since      Class available since Release 0.6.0
 */

class Blog extends Outputtable implements iTethysBase {

	/* PROPERTIES */
	
	private $id;
	private $name;
	private $type;
	private $categories;
	private $description;
	private $url;
	private $image;
	private $dateAdded;
	private $default;
	private $active;
	
	private $categoryArr;
	
	/* METHODS */
	
	public function __construct($id = 0, $name = null, $type = null, $categories = null, $description = null, $url = null, $image = null, $dateAdded = null, $default = 0, $active = 0) {
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->categories = $categories;
		$this->description = $description;
		$this->url = $url;
		$this->image = $image;
		$this->dateAdded = $dateAdded;
		$this->default = $default;
		$this->active = $active;

		$this->categoryArr = explode(',', $categories);

		// members inherited from TethyBase
		$this->indexable = true;
		$this->subscribable = true;
		$this->template = 'blog_post.php';
	}
	
	/* SEARCH */
	
	// search blogs
	// returns an array of Blog objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'blogName ASC') {
		// if search parameters are set, validate the specific parameters
		if (is_array($params)) {
			if (isset($params['id']) && is_numeric($params['id']) && $params['id'] > 0) {
				$whereParams['id'] = $params['id'];
				$where[] = "`blogId`=:id";
			}
			if (isset($params['type']) && $params['type'] != '') {
				$whereParams['type'] = $params['type'];
				$where[] = "`blogType`=:type";
			}
			if (isset($params['category']) && $params['category'] != '') {
				$whereParams['category'] = trim($params['category']);
				$where[] = "FIND_IN_SET(:category, `blogCategories`)";
			}
			if (isset($params['url']) && $params['url'] != '') {
				$whereParams['url'] = $params['url'];
				$where[] = "`blogUrl`=:url";
			}
			if (isset($params['default']) && $params['default']) {
				$whereParams['default'] = 1;
				$where[] = "`blogDefault`=:default";
			}
			if (isset($params['name']) && $params['name'] != '') {
				$whereParams['name'] = '%'.$params['name'].'%';
				$where[] = "`blogName` LIKE :name";
			}
			if (isset($params['keyword']) && $params['keyword'] != '') {
				$whereParams['kwName'] = '%'.$params['keyword'].'%';
				$whereParams['kwDesc'] = '%'.$params['keyword'].'%';
				$whereParams['kwDetails'] = '%'.$params['keyword'].'%';
				
				$where[] = "(`blogName` LIKE :kwName OR `blogDescription` LIKE :kwDesc OR `blogCategories` LIKE :kwDetails)";
			}
		}
		
		// determine whether to return inactive blogs
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for active search-parameter
			if (is_array($params)) {
				if (isset($params['active']) && is_numeric($params['active'])) {
					$whereParams['active'] = $params['active'];
					$where[] = "`blogActive`=:active";
				}
			}
		} // else, only return active comments
		else {
			$whereParams['active'] = 1;
			$where[] = "`blogActive`=:active";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$totQ = "SELECT COUNT(`blogId`) AS result FROM `blogs`";
		$query = "SELECT `blogId` AS id, ".
				"`blogName` AS name, ".
				"`blogType` AS type, ".
				"`blogCategories` AS categories, ".
				"`blogDescription` AS description, ".
				"`blogUrl` AS url, ".
				"`blogImage` AS image, ".
				"`blogDateAdded` AS dateAdded, ".
				"`blogDefault` AS def, ".
				"`blogActive` AS active ".
				"FROM `blogs`";
		
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
			$resultArr[] = new Blog($obj['id'], $obj['name'], $obj['type'], $obj['categories'], $obj['description'], $obj['url'], $obj['image'], $obj['dateAdded'], $obj['def'], $obj['active']);
		}
		
		return $resultArr;
	}
	
	// returns Blog object associated to specific blogId
	public static function getById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$tempArr = Blog::search(array('id' => $id));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	public static function getByUrl($url) {
		if ($url == '') {
			return false;
		}
		
		$tempArr = Blog::search(array('url' => $url));

		return (is_array($tempArr)) ? $tempArr[0] : false;
	}
	
	public static function getDefault() {		
		$tempArr = Blog::search(array('default' => 1));

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
			case 'name':
				if ($this->name != $value) {
					$ret = $this->setName($value);
				}
			break;
			case 'categories':
				if ($this->categories != $value) {
					$ret = $this->setCategories($value);
				}
			break;
			case 'description':
				if ($this->description != $value) {
					$ret = $this->setDescription($value);
				}
			break;
			case 'url':
				if ($this->url != $value) {
					$ret = $this->setUrl($value);
				}
			break;
			case 'default':
				if ($this->default != $value) {
					$ret = $this->setDefault($value);
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
	
	// creates a new blog
	public static function add($name, $type = post, $categories = null, $description = null, $url = null, $default = 0, $active = 1) {
		// check that the active user is logged in and an admin
		if (!User::isAdmin()) {
			SystemMessage::save(MSG_WARNING, 'You do not have permission to add blogs.');
			return false;
		}

		// validate the account information
		if ($name == '') {
			SystemMessage::save(MSG_WARNING, 'Blog name cannot be null.', 'name');
			$fail[] = true;
		}
		if (!in_array($type, array('post', 'photo'))) {
			SystemMessage::save(MSG_WARNING, 'Invalid blog type.', 'type');
			$fail[] = true;
		}
		if ($url != '' && !Blog::isUrlAvailable($url)) {
			SystemMessage::save(MSG_WARNING, 'This url is not available.', 'url');
			$fail[] = true;
		}
		
		if (is_array($fail) && in_array(true, $fail)) {
			return false;
		}

		$default = ($default === 1) ? 1 : 0;
		$active = ($active === 0) ? 0 : 1;
		
		// strip extra whitespace between commas
		$tempArr = explode(',', $categories);
		
		if (is_array($tempArr)) {
			foreach ($tempArr as $t) {
				$tempArr2[] = trim($t);
			}
			
			$categories = implode(',', $tempArr2);
		}
		
		if ($default == 1) {
			$query = "UPDATE `blogs` SET `blogDefault`=0";
			$GLOBALS['dbObj']->update($query);
		}
		
		// insert blog info into db
		$query = "INSERT INTO `blogs` (`blogName`, `blogType`, `blogCategories`, `blogDescription`, `blogUrl`, `blogActive`, `blogDefault`, `blogDateAdded`) VALUES (:name, :type, :categories, :description, :url, :active, :default, :date)";
		
		$dt = new DateTime('now', new DateTimeZone(DATE_DEFAULT_TIMEZONE));
		
		$params = array(
			'name' => $name,
			'type' => $type,
			'categories' => $categories,
			'description' => $description,
			'url' => $url,
			'active' => $active,
			'default' => $default,
			'date' => $dt->format(DATE_SQL_FORMAT)
		);

		try {
			return $GLOBALS['dbObj']->insert($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error creating blog.');
			return false;
		}
	}
	
	// remove a blog
	public function delete() {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		$query = "DELETE FROM `blogs` WHERE `blogId`=:id";
		$params = array('id' => $this->id);
		
		try {
			// @todo: remove posts and photos
			$ret = $GLOBALS['dbObj']->delete($query, $params);
			$this->refresh();
			return $ret;
		} catch (Exception $e) {
			return false;
		}
	}	
	
	// update the blog's name
	public function setName($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		if ($val == '') {
			SystemMessage::save(MSG_WARNING, 'Blog name cannot be null.', 'name');
			return false;
		}
		
		$query = "UPDATE `blogs` SET `blogName`=:val WHERE `blogId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog.');
			return false;
		}
	}
	
	// update the blog's categories
	public function setCategories($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		// strip extra whitespace between commas
		$tempArr = explode(',', $val);
		
		if (is_array($tempArr)) {
			foreach ($tempArr as $t) {
				$tempArr2[] = trim($t);
			}
			
			$val = implode(',', $tempArr2);
		}
		
		$query = "UPDATE `blogs` SET `blogCategories`=:val WHERE `blogId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog.');
			return false;
		}
	}
	// update the blog's description
	public function setDescription($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		$query = "UPDATE `blogs` SET `blogDescription`=:val WHERE `blogId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog.');
			return false;
		}
	}

	// update the blog's url
	public function setUrl($val = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		if ($url != '' && !Blog::isUrlAvailable($url)) {
			SystemMessage::save(MSG_WARNING, 'This url is not available.', 'url');
			return false;
		}
		
		$query = "UPDATE `blogs` SET `blogUrl`=:val WHERE `blogId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			$ret = $GLOBALS['dbObj']->update($query, $params);
			$this->refresh();
			return $ret;
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog.');
			return false;
		}
	}
	
	// update the blog's default status
	public function setDefault($val = 0) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		$val = ($val === 1) ? 1 : 0;
		
		if ($val == 1) {
			$query = "UPDATE `blogs` SET `blogDefault`=0";
			$GLOBALS['dbObj']->update($query);
		}
		
		$query = "UPDATE `blogs` SET `blogDefault`=:val WHERE `blogId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog.');
			return false;
		}
	}

	// update the blog's active status
	public function setActive($val = 1) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to edit this blog.');
			return false;
		}
		
		$val = ($val === 0) ? 0 : 1;
		
		$query = "UPDATE `blogs` SET `blogActive`=:val WHERE `blogId`=:id";
		$params = array('id' => $this->id,
			'val' => $val
		);
		
		try {
			return $GLOBALS['dbObj']->update($query, $params);
		} catch(Exception $e) {
			SystemMessage::save(MSG_ERROR, 'Error updating blog.');
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
		if (!$this->active) {
			$ret = User::isAdmin();
		} else {
			$ret = true;
		}
		
		return $ret;
	}
	
	/* IMAGES */
	
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
		$query = "UPDATE `blogs` SET `blogImage`=:image WHERE `blogId`=:id";
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
		$query = "UPDATE `blogs` SET `blogImage`=:image WHERE `blogId`=:id";
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
	
	/* POSTS */
	
	public function getPosts($params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'bpDatePosted DESC') {
		$params['blog'] = $this->id;
		
		return BlogPost::search($params, $perPage, $pageNum, $totalResults, $sort);
	}
	
	public function getPostById($id) {
		$post = BlogPost::getById($id);
		
		if (!$post) {
			return false;
		}
		
		return ($post->blog == $this->id) ? $post : false;
	}
	
	public function addPost($value, $title = null, $tags = null, $comments = 'open', $excerpt = null, $url = null, $datePosted = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if (BlogPost::add($this->id, $value, $title, $tags, $comments, $excerpt, $url, $datePosted)) {
			$this->refresh();
			return true;
		} else {
			return false;
		}
	}
	
	public function editPost(&$post, $value, $title = null, $tags = null, $comments = 'open', $excerpt = null, $url = null, $datePosted = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if (!$post || !($post instanceof BlogPost)) {
			return false;
		}
		
		if ($post->blog != $this->id) {
			return false;
		}

		$pass[] = true;
		if ($post->title != $title) {
			$pass[] = $post->setTitle($title);
		}
		if ($post->tags != $tags) {
			$pass[] = $post->setTags($tags);
		}
		if ($post->excerpt != $excerpt) {
			$pass[] = $post->setExcerpt($excerpt);
		}
		if ($post->url != $url) {
			$pass[] = $post->setUrl($url);
		}
		if ($post->comments != $comments) {
			$pass[] = $post->setComments($comments);
		}
		if ($post->datePosted != $datePosted) {
			$pass[] = $post->setDatePosted($datePosted);
		}
		if ($post->value != $value) {
			$pass[] = $post->setValue($value);
		}
			
		if (is_array($pass) && !in_array(false, $pass)) {
			$this->refresh();
			return true;
		} else {
			return false;
			$ret = array('error' => 'Unable to save all fields');
		}
	}
	
	// deletes blog post
	public function removePost(&$post) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if (!$post || !($post instanceof BlogPost)) {
			return false;
		}
		
		if ($post->blog != $this->id) {
			return false;
		}
		
		if ($post->delete()) {
			$this->refresh();
			return true;
		} else {
			return false;
		}
	}
	
	/* PHOTO POSTS */
	
	public function getPhotos($params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = 'bpDatePosted DESC') {
		$params['blog'] = $this->id;

		return BlogPhoto::search($params, $perPage, $pageNum, $totalResults, $sort);
	}
	
	public function getPhotoById($id) {
		$photo = BlogPhoto::getById($id);
		
		if (!$photo) {
			return false;
		}
		
		return ($photo->blog == $this->id) ? $photo : false;
	}
	
	public function addPhoto($file, $title = null, $caption = null, $tags = null, $datePosted = null) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if ($file == '') {
			return false;
		}
		
		$exif = exif_read_data(CORE_DIR_DEPTH.'uploads/'.$file);
		
		if ($exif) {
			$tempDt = new DateTime($exif['DateTimeOriginal'], new DateTimeZone(DATE_DEFAULT_TIMEZONE));
			$datePosted = $tempDt->format(DATE_SQL_FORMAT);
		}
		
		try {
			$GLOBALS['dbObj']->beginTransaction();
			
			if (BlogPhoto::add($this->id, null, $file, $title, $tags, $caption, $datePosted)) {
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
					SystemMessage::log(MSG_ERROR, 'Error copying blog photo: '.$file);
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
	
	// deletes blog photo
	public function removePhoto(&$photo) {
		if (!$this->canEdit(__FUNCTION__)) {
			SystemMessage::save(MSG_ERROR, 'You do not have permission to perform that action.');
			return false;
		}
		
		if (!$photo || !($photo instanceof BlogPhoto)) {
			return false;
		}
		
		if ($photo->blog != $this->id) {
			return false;
		}
		
		return $photo->delete();
	}
	
	/* OUTPUT */
	
	public function getOutputParams() {
		$params = array(
			'name' => $this->name,
			'icon' => $this->imagePath,
			'baseUrl' => $this->fullUrl,
			'description' => $this->description,
			'tags' => $this->getTagsArr(),
			'archives' => $this->getArchiveArr(),
			'rss' => CORE_RSS_DIR.$this->getRssFile()
		);
		
		if ($this->type == 'photo') {
			// get photos
			if ($_GET['tag'] != '') {
				$search['tag'] = $_GET['tag'];
			}
			if (is_numeric($_GET['month']) && $_GET['month'] > 0) {
				$search['month'] = $_GET['month'];
			}
			if (is_numeric($_GET['year']) && $_GET['year'] > 0) {
				$search['year'] = $_GET['year'];
			}
			
			$photos = $this->getPhotos($search, 1000, 1, $totalResults);
			
			$params['photos'] = $photos;
			$params['totalPhotos'] = $totalResults;
		} else {
			// get posts
			$perPage = (is_numeric($_GET['perpage']) && $_GET['perpage'] > 0) ? $_GET['perpage'] : 10;
			$pageNum = (is_numeric($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
			
			if ($_GET['search'] != '') {
				$search['keyword'] = $_GET['search'];
			}
			if ($_GET['tag'] != '') {
				$search['tag'] = $_GET['tag'];
			}
			if (is_numeric($_GET['month']) && $_GET['month'] > 0) {
				$search['month'] = $_GET['month'];
			}
			if (is_numeric($_GET['year']) && $_GET['year'] > 0) {
				$search['year'] = $_GET['year'];
			}
			
			$posts = $this->getPosts($search, $perPage, $pageNum, $totalResults);
			
			$params['posts'] = $posts;
			$params['totalPosts'] = $totalResults;
			$params['pageNum'] = $pageNum;
			$params['perPage'] = $perPage;
		}
		
		return $params;
	}
	
	public function outputSearchDetails($url = null) {
		return;
	}
	
	public static function getSitemapParams() {
		$query = "SELECT `bpId` AS id, `bpUrl` AS url, `bpDatePosted` AS date, `blogId` AS bId, `blogUrl` AS bUrl FROM `blog_posts` LEFT JOIN `blogs` ON `bpBlogId`=`blogId` WHERE `blogActive`=1 AND `bpDatePosted`<=NOW()" . addQuerySort('bpDatePosted DESC');
		
		// execute the query
		$results = $GLOBALS['dbObj']->select($query);

		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$url = CORE_DOMAIN.'blogs/';
			$url .= ($obj['bUrl'] != '') ? $obj['bUrl'] : $obj['bId'];
			$url .= '/';
			$url .= ($obj['url'] != '') ? $obj['url'] : $obj['id'];

			$resultArr[] = array('group' => 'blogs', 'url' => $url, 'lastmod' => $obj['date'], 'frequency' => 'monthly', 'priority' => 0.5);
		}
		
		return $resultArr;
	}
	
	public function outputPost(&$post) {
		if (!($post && ($post instanceof BlogPost))) {
			return null;
		}
		
		$post->output();
		return;
	}
	
	public function getTagsArr($limit = 30, $sort = 'bptValue ASC') {
		if ($this->type == 'photo') {
			$table = 'blog_photo_tags';
			$jTable = 'blog_photos';
		} else {
			$table = 'blog_post_tags';
			$jTable = 'blog_posts';
		}
		
		$query = "SELECT COUNT(`bptId`) AS total, `bptValue` FROM `".$table."` LEFT JOIN `".$jTable."` ON `bptId`=`bpId` WHERE `bpBlogId`=:id AND `bpDatePosted`<=NOW() GROUP BY `bptValue`" . addQuerySort($sort) . addQueryLimit($limit);
		$params = array('id' => $this->id);

		$results = $GLOBALS['dbObj']->select($query, $params);
		
		if (!$results) {
			return false;
		}
		
		$max = 0;
		
		foreach ($results as $r) {
			if ($r['total'] > $max) {
				$max = $r['total'];
			}
			
			$res[] = array('value' => $r['bptValue'], 'count' => $r['total'], 'weight' => '');
		}
		
		if (is_array($res)) {
			for ($i = 0; $i < count($res); $i++) {
				$percent = floor($res[$i]['count']/$max*100);

				if ($percent < 20) {
					$class = 'smallest';
				} else if ($percent < 40) {
					$class = 'small';
				} else if ($percent < 60) {
					$class = 'medium';
				} else if ($percent < 80) {
					$class = 'large';
				} else {
					$class = 'largest';
				}
				
				$res[$i]['weight'] = $class;
			}
		}

		return $res;
	}
	
	public function getArchiveArr($limit = null) {
		$table = ($this->type == 'photo') ? 'blog_photos' : 'blog_posts';
		
		if (!$limit) {
			$limit = 9999;
		}
		
		$query = "SELECT YEAR(`bpDatePosted`) AS y, Month(`bpDatePosted`) AS m, COUNT(`bpId`) AS total FROM `".$table."` WHERE `bpBlogId`=:id AND `bpDatePosted`<=NOW() GROUP BY y DESC, m DESC";
		$params = array('id' => $this->id);
		
		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $params);
		
		if (!$results) {
			return false;
		}
		
		$MONTHS = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

		foreach ($results as $obj) {
			$resultArr[] = array('year' => $obj['y'], 'month' => $obj['m'], 'total' => $obj['total'], 'label' => $MONTHS[($obj['m']-1)].' '.$obj['y']);
			
			if ($limit-- < 1) {
				break;
			}
		}
		
		return $resultArr;
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
	
	public function getFullUrl($includeDomain = false) {
		$ret = ($includeDomain) ? CORE_DOMAIN.'blogs/' : '/blogs/';
		
		$ret .= ($this->url != '') ? $this->url : $this->id;
		
		return $ret;
	}
	
	public function getNumPosts($future = false) {
		$table = ($this->type == 'photo') ? 'blog_photos' : 'blog_posts';
		
		$query = "SELECT COUNT(`bpId`) AS result FROM `".$table."` WHERE `bpBlogId`=:id";
		
		if (!$future) {
			$query .= " AND `bpDatePosted`<=NOW()";
		}
		
		$params = array('id' => $this->id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	public function getLastPostDate() {
		$table = ($this->type == 'photo') ? 'blog_photos' : 'blog_posts';
		
		$query = "SELECT `bpDatePosted` AS result FROM `".$table."` WHERE `bpBlogId`=:id";
		
		if (!$future) {
			$query .= " AND `bpDatePosted`<=NOW()";
		}
		
		$query .= addQuerySort('bpDatePosted DESC') . addQueryLimit(1);
		
		$params = array('id' => $this->id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
	
	public static function isUrlAvailable($url, $type = 'blog') {
		if ($url == '') {
			return false;
		}

		if ($url == 'blog' || $url == 'blogs' || is_numeric($url)) {
			return false;
		}
		
		// check is valid url
		$pattern = '/^[a-zA-Z0-9_\-]{4,80}$/i';
		if (!preg_match($pattern, $url)) {
			return false;
		}
		
		// check that the url is not already used by another blog/post
		if ($type == 'post') {
			$query = "SELECT `bpId` AS result FROM `blog_posts` WHERE UPPER(`bpUrl`)=:url";
		} else {
			$query = "SELECT `blogId` AS result FROM `blogs` WHERE UPPER(`blogUrl`)=:url";
		}
		
		$params = array('url' => strtoupper($url));
		
		try {
			return !$GLOBALS['dbObj']->fetchResult($query, $params);
		} catch(Exception $e) {
			return false;
		}
		
		return false;
	}
	
	public static function getList($sort = 'bpDatePosted ASC', $showInactive = false) {
		// determine whether to return inactive page
		// if user is an admin, they can see both
		if (User::isAdmin()) {
			// check for active search-parameter
			if (!$showInactive) {
				$whereParams['active'] = 1;
				$where[] = "`blogActive`=:active";
			}
		} // else, only return active pages
		else {
			$whereParams['active'] = 1;
			$where[] = "`blogActive`=:active";
		}
		
		// build the where clause
		$whereClause = addQueryWhere($where);
		
		// build the search query
		$query = "SELECT `blogId` AS id, ".
				"`blogType` AS type, ".
				"`blogName` AS name, ".
				"`blogDateAdded` AS date ".
				"FROM `blogs`";
		
		$query .= $whereClause . addQuerySort($sort);

		// execute the query
		$results = $GLOBALS['dbObj']->select($query, $whereParams);
		
		if (!$results) {
			return false;
		}
		
		foreach ($results as $obj) {
			$resultArr[] = array('id' => $obj['id'], 'type' => $obj['type'], 'title' => $obj['name'], 'date-created' => $obj['date']);
		}
		
		return $resultArr;
	}
	
	public static function getNameById($id) {
		if (!is_numeric($id) || $id < 1) {
			return false;
		}
		
		$query = "SELECT `blogName` AS result FROM `blogs` WHERE `blogId`=:id";
		$params = array('id' => $id);
		
		return $GLOBALS['dbObj']->fetchResult($query, $params);
	}
};

?>