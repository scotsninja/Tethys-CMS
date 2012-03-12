<?php
/**
 * Handles individual feed items
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.7.0
 * @since      Class available since Release 0.7.0
 */

class RssFeedItem {

	/* PROPERTIES */
	
	private $title;
	private $link;
	private $description;
	private $author;
	private $category;
	private $comments;
	private $guid;
	private $pubDate;
	
	
	/* METHODS */
	
	public function __construct($title = null, $link = null, $description = null, $category = null, $comments = null, $pubDate =null) {
		$this->title = $title;
		$this->link = $link;
		$this->description = $description;
		$this->category = $cateogyr;
		$this->comments = $comments;
		
		$dt = new DateTime($pubDate);
		$this->pubDate = $dt->format('D, d M Y H:i:s e');
		
		$this->guid = substr(md5($link), 0, 10);
		$this->author = SITE_AUTHOR;
	}
	
	public function __get($var) {
		return (isset($this->$var)) ? $this->$var : false;
	}
}

?>