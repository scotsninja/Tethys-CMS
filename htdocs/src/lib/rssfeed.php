<?php
/**
 * Administer RSS feeds
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.7.0
 * @since      Class available since Release 0.7.0
 */

class RssFeed {

	/* PROPERTIES */
	
	private $type;						// the object-type for this RSS feed (Blog, Project, etc)
	private $typeId;					// the object-id for this feed
	private $obj;						// the parent obj of the feed;
	
	private $title;
	private $link;
	private $copyright;
	private $description;
	private $webMaster;
	private $pubDate;
	private $lastBuildDate;
	private $category;
	private $ttl;
	private $image;
	private $language;
	private $generator;
	
	private $items;						// array of RssFeedItems
	private $file;
	
	/* METHODS */
	
	public function __construct($type, $typeId) {
		$this->type = $type;
		$this->typeId = $typeId;
		
		// load the parent object and set fields accordingly
		$this->loadObj();
		
		if ($this->obj) {
			$this->title = $this->obj->name;
			$this->link = CORE_DOMAIN.substr($this->obj->fullUrl, 1);
			$this->description = $this->obj->description;
			$this->image = CORE_DOMAIN.substr($this->obj->imagePath,1);
			$this->category = $this->obj->categories;
			
			$this->file = $this->obj->getRssFile();
		}
		
		$this->loadItems();
		
		$dt = new DateTime('now', new DateTimeZone(DATE_DEFAULT_TIMEZONE));
		$this->lastBuildDate = $dt->format('r');
		
		$dt->setTime(0, 0, 0);
		$this->pubDate = $dt->format('r');
		
		$this->ttl = 60;
		$this->webMaster = CORE_WEBMASTER.' ('.SITE_AUTHOR.')';
		$this->generator = 'TethysCMS '.CORE_VERSION;
		$this->copyright = 'Copyright '.$dt->format('Y').' 20xx Productions';
		$this->language = 'en-us';
	}
	
	public function __get($var) {
		return (isset($this->$var)) ? $this->$var : false;
	}
	
	public function __set($var, $value) {
		return $this->$var = $value;
	}
	
	// loads new items and writes to RSS feed file
	public function save($rssFile) {
		if ($rssFile == '') {
			throw new Exception('Invalid rss file');
		}

		$rss = $this->output();

		// open file
		$handle = @fopen(CORE_RSS_DIR.$rssFile, 'w+');
		
		if ($handle) {
			// save file
			fwrite($handle, $rss);
			
			fclose($handle);
			return true;
		} else {
			throw new Exception('Unable to open RSS file for writing: '.$rssFile);
			return false;
		}
		
	}
	
	// returns an xml string of the feed
	private function output() {
		$ret =  '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$ret .=  '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
		$ret .=  '<channel>'."\n";
		$ret .=  '<atom:link href="'.CORE_DOMAIN.CORE_RSS_DIR.$this->file.'" rel="self" type="application/rss+xml" />'."\n";
		
			// title
			$ret .=  '<title>'.$this->title.'</title>'."\n";
			
			// link
			$ret .=  '<link>'.$this->link.'</link>'."\n";
			
			// description
			$ret .=  '<description>'.$this->description.'</description>'."\n";
			
			// language
			if ($this->language != '') {
				$ret .=  '<language>'.$this->language.'</language>'."\n";
			}
			
			// image
			if ($this->image != '') {
				$ret .= '<image>
					<url>'.$this->image.'</url>
					<title>'.$this->title.'</title>
					<link>'.$this->link.'</link>
				</image>'."\n";
			}
			
			// categories
			if ($this->categories != '') {
				$catArr = explode(',', $this->categories);
				
				foreach ($catArr as $cat) {
					$ret .=  '<category>'.$cat.'</category>'."\n";
				}
			}
			
			// pubDate
			if ($this->pubDate != '') {
				$ret .=  '<pubDate>'.$this->pubDate.'</pubDate>'."\n";
			}
			
			// lastBuildDate
			if ($this->lastBuildDate != '') {
				$ret .=  '<lastBuildDate>'.$this->lastBuildDate.'</lastBuildDate>'."\n";
			}
			
			// generator
			if ($this->generator != '') {
				$ret .=  '<generator>'.$this->generator.'</generator>'."\n";
			}
			
			// webMaster
			if ($this->webMaster != '') {
				$ret .=  '<webMaster>'.$this->webMaster.'</webMaster>'."\n";
			}
			
			// copyright
			if ($this->copyright != '') {
				$ret .=  '<copyright>'.$this->copyright.'</copyright>'."\n";
			}
			
			// ttl
			if ($this->ttl != '') {
				$ret .=  '<ttl>'.$this->ttl.'</ttl>'."\n";
			}
			
			// items
			if (is_array($this->items)) {
				foreach ($this->items as $item) {
					$ret .=  '<item>
						<title>'.$item->title.'</title>
						<link>'.$item->link.'</link>
						<description><![CDATA['.$item->description.']]></description>
						<pubDate>'.$item->pubDate.'</pubDate>
						<guid isPermaLink="false">'.$item->guid.'</guid>
						<author>'.$item->author.'</author>
						<comments>'.$item->comments.'</comments>';
						
					if ($item->category != '') {
						$catArr = explode(',', $item->category);
						
						if (is_array($catArr)) {
							foreach ($catArr as $cat) {
								$ret .= '<category>'.$cat.'</category>';
							}
						}
					}
					
					$ret .= '</item>'."\n";
				}
			}
		$ret .=  '</channel>'."\n";
		$ret .=  '</rss>'."\n";
		
		return $ret;
	}
	
	// loads and stores the object
	private function loadObj() {
		$blankObj = new $this->type();
		$obj = $blankObj->getById($this->typeId);
		
		$this->obj = $obj;
	}
	
	// @todo: only works with blogs and posts
	// retrieve feed items and store in $this->items
	private function loadItems() {
		if (!$this->obj) {
			return false;
		}
		
		$limit = 20;
		
		$posts = $this->obj->getPosts(array('future' => false), $limit);
		
		if (!is_array($posts)) {
			return false;
		}
		
		foreach ($posts as $p) {
			$url = CORE_DOMAIN.substr($p->fullUrl,1);
			$this->items[] = new RssFeedItem($p->title, $url, $p->getBlurb(500, false), $p->tags, null, $p->datePosted);
		}
	}
}

?>