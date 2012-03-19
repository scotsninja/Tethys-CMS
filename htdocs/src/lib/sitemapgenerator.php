<?php
/**
 * Generate a sitemap
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.8.0
 * @since      Class available since Release 0.8.0
 */

class SitemapGenerator {

	/* MEMBERS */
	
	private $type;		// type of sitemap: xml, html, text
	private $file;		// file to save the sitemap
	
	private $map;		// stores the sitemap data
	private $mapSet;	// temporary variable for writing xml sitemaps
	
	/* METHODS */
	
	public function __construct($type = 'xml', $file = 'sitemap.xml') {
		$this->type = $type;
		$this->file = $file;
	}
	
	public function __get($var) {
		return (isset($this->$var)) ? $this->$var : null;
	}
	
	public function __set($var, $val) {
		return (isset($this->$var)) ? $this->$var = $val : false;
	}
	
	// opens the sitemap and saves it into $this->map
	public function load() {
		if (!file_exists($this->file)) {
			throw new Exception('File does not exist');
			return false;
		}
		
		if ($type == 'xml') {
			$map = simplexml_load_file($this->file);
		} else if ($type == 'html') {
			// @todo
		} else {
			$fh = fopen($this->file, 'r');
			
			if ($fh) {
				$map = '';
				while (!feof($fh)) {
				  $map .= fread($fh, 8192);
				}
				fclose($fh);
			}
		}
		
		$this->map = $map;
		return true;
	}
	
	/* SAVING */
	
	// updates the sitemap (creates if one does not exist) with the passed data
	// can accept multiple arrays
	public function update() {
		$argList = func_get_args();
		$this->map = null;
		$this->mapSet = null;

		// loop through all passed arrays, building the map
		for ($i = 0; $i < func_num_args(); $i++) {
			$data = $argList[$i];
			
			if (is_array($data) && count($data) > 0) {
				$this->updateMap($data);
			}
		}
		
		// make sure the file exists
		$this->create();
		
		// write the changes to file
		if ($this->type == 'text') {
			$ret = $this->writeText();
		} else if ($this->type == 'html') {
			$ret = $this->writeHTML();
		} else {
			$ret = $this->writeXML();
		}
		
		return $ret;
	}
	
	// formats and stores the sitemap data in $this->map
	private function updateMap($data) {
		if ($this->type == 'text') {
			foreach ($data as $d) {
				if ($d != '') {
					$this->map .= $d."\n";
				}
			}
		} else if ($this->type == 'html') {
			foreach ($data as $d) {
				$this->map[] = $d;
			}
			
			// sort map
			foreach ($this->map as $key => $row) {
				$group[$key] = $row['group'];
				$url[$key] = $row['url'];
			}
			
			array_multisort($group, SORT_ASC, url, SORT_ASC, $this->map);
		} else if ($this->type == 'xml') {
			if (!$this->map) {
				// document root
				$this->map = new DOMDocument('1.0', 'UTF-8');
				$this->map->formatOutput = true;
			
				// <urlset>
				$this->mapSet = $this->map->createElement('urlset');
				
				$urlSetAttr = $this->map->createAttribute('xmlns');
				$urlSetAttr->value = 'http://www.sitemaps.org/schemas/sitemap/0.9';
				$this->mapSet->appendChild($urlSetAttr);
			}
			
			
			// loop through data and add <url>s
			foreach ($data as $d){
				if ($d['url'] != '') {
					$url = $this->map->createElement('url');

					if ($d['lastmod'] != '') {
						$dt = (!($d['lastmod'] instanceof DateTime)) ? new DateTime($d['lastmod'], new DateTimeZone(DATE_DEFAULT_TIMEZONE)) : $d['lastmod'];
					} else {
						$dt = new DateTime('now', new DateTimeZone(DATE_DEFAULT_TIMEZONE));
					}
					
					//$locVal = rawurlencode($d['url']);
					$locVal = str_replace(array('&', '<', '>', '"', "'"), array('&amp;', '&lt;', '&gt;', '&quot;', '&apos;'), $d['url']);
					$lastmodVal = $dt->format('c');
					$frequencyVal = (SitemapGenerator::isValidFrequency($d['frequency'])) ? $d['frequency'] : 'monthly';
					$priorityVal = ($d['priority'] < 0.1 || $d['priority'] > 1) ? 0.5 : $d['priority'];
					
					// add location node
					$loc = $this->map->createElement('loc', $locVal);
					$url->appendChild($loc);
					
					// add lastmod node
					$lastmod = $this->map->createElement('lastmod', $lastmodVal);
					$url->appendChild($lastmod);
					
					// add changefreq node
					$changefreq = $this->map->createElement('changefreq', $frequencyVal);
					$url->appendChild($changefreq);
					
					// add priority node
					$priority = $this->map->createElement('priority', $priorityVal);
					$url->appendChild($priority);
					
					$this->mapSet->appendChild($url);
				}
			}
		}
		
	}
	
	// create an HTML sitemap
	private function writeHTML() {
		$template = CORE_DIR_DEPTH.CORE_TEMPLATE_DIR.'sitemap.php.txt';
		
		if (!file_exists($template)) {
			throw new Exception('Cannot find template: '.$template);
			return false;
		}
		
		// load contents of template
		$fh = fopen($template, 'r');
			
		if ($fh) {
			$temp = '';
			while (!feof($fh)) {
			  $temp .= fread($fh, 8192);
			}
			fclose($fh);
		} else {
			throw new Exception('Could not open sitemap template.');
			return false;
		}
		
		// open file for writing
		$fh = fopen($this->file, 'w');
		
		if (!$fh) {
			throw new Exception('Could not open sitemap for writing.');
			return false;
		}
		
		$html = '';
		$tempGroup = '';
		$i = 0;
		
		foreach ($this->map as $m) {
			if ($m['group'] != $tempGroup) {
				$tempGroup = $m['group'];
				$html .= ($i++ > 0) ? "</ul><br />\r\n" : '';
				$html .= '<h3>'.ucwords($m['group'])."</h3>\r\n";
				$html .= "<ul>\r\n";
			}
			
			$html .= '<li><a href="'.$m['url'].'">'.$m['url']."</a></li>\r\n";
		}
		
		$ret = fwrite($fh, preg_replace('/#SITEMAP-CONTENT#/', $html, $temp));
		
		fclose($fh);
		
		return ($ret > 0);
	}
	
	// write a text sitemap
	private function writeText() {
		$fh = fopen($this->file, 'w');
		
		if (!$fh) {
			throw new Exception('Unable to open file for writing.');
			return false;
		}
		
		$ret = fwrite($fh, $this->map);
		
		fclose($fh);
		
		return ($ret > 0);
	}
	
	// @todo: validate
	// write xml sitemap based off Sitemap Protocol 0.9
	private function writeXML() {
		$this->map->appendChild($this->mapSet);
		
		return ($this->map->save($this->file)) ? true : false;
	}
	
	/* UTILTIES */
	
	// creates the file on the server
	private function create() {
		if (file_exists($this->file)) {
			return true;
		}
		
		return touch($this->file);
	}
	
	// deletes the sitemap from the server
	private function remove() {
		if (!file_exists($this->file)) {
			return true;
		}
		
		return @unlink($this->file);
	}
	
	// @todo
	public function validate() {
	}
	
	// returns true if the passed value is a valid frequency value for xml sitemaps
	public static function isValidFrequency($freq) {
		return in_array($freq, array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'));
	}
}

?>