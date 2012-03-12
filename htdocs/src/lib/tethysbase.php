<?php
/* Copyright 20xx Productions */

abstract class TethysBase {

	/* MEMBERS */
	
	protected $indexable = true;
	protected $subscribable = false;
	protected $template;
	
	/* METHODS */
	
	/* Display */
	
	final public function output() {
		if (!$this->canView()) {
			throw new Exception('You do not have permission to view this.');
		}
		
		if ($this->template == '' || !file_exists(CORE_TEMPLATE_DIR.$this->template)) {
			throw new Exception('Error loading template.');
		}
		
		$params = $this->getOutputParams();
		
		include(CORE_TEMPLATE_DIR.$this->template);
		return;
	}
	
	final public static function outputSearch($params = null) {
		$template = 'obj_search.php';
		
		if ($template == '' || !file_exists(CORE_TEMPLATE_DIR.$template)) {
			throw new Exception('Error loading template.');
		}
		
		include(CORE_TEMPLATE_DIR.$template);
		return;
	}
	
	abstract public function getOutputParams();
	abstract public function outputSearchDetails($url);
	abstract public function canView();
	
	/* RSS */
	
	final public function updateRss() {
		$file = $this->getRssFile();
		$rssObj = new RssFeed(get_class($this), $this->id);

		try {
			$rssObj->save($file);
		} catch (Exception $e) {
			SystemMessage::log(MSG_FATAL, 'Error saving RSS feed');
			return false;
		}
	}
	
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
			array('url' => CORE_DOMAIN.'homepage', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'yearly', 'priority' => 0.5),
			array('url' => CORE_DOMAIN.'blogs', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'weekly', 'priority' => 0.9),
			array('url' => CORE_DOMAIN.'code_samples', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'monthly', 'priority' => 0.5),
			array('url' => CORE_DOMAIN.'contact', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'never', 'priority' => 0.5),
			array('url' => CORE_DOMAIN.'portfolio', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'monthly', 'priority' => 0.7),
			array('url' => CORE_DOMAIN.'projects', 'lastmod' => '2012-03-08 12:00:00', 'frequency' => 'weekly', 'priority' => 0.7)
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
			return false;
		}
	}
	
	abstract public static function getSitemapParams();
	
	/* Misc */
	
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