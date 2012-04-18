<?php
/**
 * Abstract class handling html, sitemap, and rss feed output
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.8.0
 * @since      Class available since Release 0.8.0
 */

abstract class Outputtable {

	/* PROPERTIES */
	
	protected $indexable = true;				// if true, the object will be included in the sitemap
	protected $subscribable = false;			// if true, the object will generate an RSS feed
	protected $template;						// the template file to display the object
	
	/* METHODS */
	
	/* Display */
	
	// outputs the object's details page
	final public function output() {
		if (!$this->canView()) {
			throw new Exception('You do not have permission to view this.');
			return;
		}
		
		if ($this->template == '' || !file_exists(CORE_TEMPLATE_DIR.$this->template)) {
			throw new Exception('Error loading template.');
			return;
		}
		
		$params = $this->getOutputParams();
		
		include(CORE_TEMPLATE_DIR.$this->template);
		return;
	}
	
	// outputs a search page for the class
	final public static function outputSearch($params = null, $template = null) {
		$template = ($template != '') ? $template : 'obj_search.php';
		
		if ($template == '' || !file_exists(CORE_TEMPLATE_DIR.$template)) {
			throw new Exception('Error loading template.');
			return;
		}
		
		include(CORE_TEMPLATE_DIR.$template);
		return;
	}
	
	// returns an array of object details passed to the template
	abstract public function getOutputParams();
	
	// returns array of details passed to the search page template
	abstract public function outputSearchDetails($url);
	
	// returns true if the current user can view the object
	abstract public function canView();
	
	/* RSS */
	
	// saves the RSS feed
	final public function updateRss() {
		$file = $this->getRssFile();
		$rssObj = new RssFeed(get_class($this), $this->id);

		try {
			$rssObj->save($file);
		} catch (Exception $e) {
			SystemMessage::log(MSG_FATAL, 'Error updating RSS feed: '.$e->getMessage());
			throw new Exception('Error updating RSS feed.');
			return false;
		}
	}
	
	// returns string for RSS file link
	final public function getRssFile() {
		if (get_class($this) == 'Project') {
			$file = $this->name.'.rss';
		} else {
			$file = $this->url.'.rss';
		}
		
		return makeFileSafe($file);
	}
	
	
	/* Sitemap */
	
	final public function updateSitemap() {
		// core pages
		$corePages = array(
			array('group' => 'pages', 'url' => CORE_DOMAIN, 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'yearly', 'priority' => 0.5),
			array('group' => 'pages', 'url' => CORE_DOMAIN.'blogs', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'weekly', 'priority' => 0.9),
			array('group' => 'pages', 'url' => CORE_DOMAIN.'code_samples', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'monthly', 'priority' => 0.5),
			array('group' => 'pages', 'url' => CORE_DOMAIN.'contact', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'never', 'priority' => 0.5),
			array('group' => 'pages', 'url' => CORE_DOMAIN.'portfolio', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'monthly', 'priority' => 0.7),
			array('group' => 'pages', 'url' => CORE_DOMAIN.'projects', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'weekly', 'priority' => 0.7)
		);
		
		// pages
		$pagePages = Page::getSitemapParams();
		
		// projects
		$projectPages = Project::getSitemapParams();
		
		// blog posts
		$blogPages = Blog::getSitemapParams();
		
		$smg = new SitemapGenerator('xml', CORE_DIR_DEPTH.'sitemap.xml');
		
		try {
			return $smg->update($corePages, $pagePages, $projectPages, $blogPages);
		} catch (Exception $e) {
			SystemMessage::log(MSG_ERROR, 'Error updating sitemap: '.$e->getMessage());
			throw new Exception('Error updatint sitemap.');
			return;
		}
	}
	
	// returns array of parameters passed to the sitemap generator
	abstract public static function getSitemapParams();
	
	/* MISC */
	
	// updates the RSS feed and sitemap for the object
	public function refresh() {
		if ($this->subscribable) {
			$this->updateRss();
		}

		if ($this->indexable) {
			$this->updateSitemap();
		}
	}
}

?>