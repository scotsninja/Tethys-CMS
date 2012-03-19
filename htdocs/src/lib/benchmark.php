<?php
/**
 * Tracks and stores benchmarking data related to page load times
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.5.0
 * @since      Class available since Release 0.5.0
 */

class Benchmark {

	/* PROPERTIES */
	
	private $benchmarkLevel;			// 0 - none, 1 - basic (script ends), 2 - verbose (page loads; requires ajax call)
	private $startTime;					// stores the start time of script execution
	private $pageId;					// unqiue hash used to group times from the same script execution/page view
	private $page;						// the page being viewed
	private $vars;						// any GET parameters in the url
	
	/* METHODS */
	
	public function __construct($level = 0, $startTime = null, $pageId = null, $page = null, $vars = null) {
		$this->benchmarkLevel = $level;
		$this->startTime = $startTime;
		
		$this->pageId = ($pageId != '') ? $pageId : substr(md5($startTime), 0, 12);
		$this->page = ($page != '') ? $page : $_GET['url'];
		$this->vars = ($vars != '') ? $vars : $_SERVER['QUERY_STRING'];
	}
	
	public function __get($var) {
		return (isset($this->$var)) ? $this->$var : false;
	}
	
	// calculate time since start and write to db
	public function log($reqLevel = 2, $action = null, $notes = null, $scriptEnd = false) {
		if ($reqLevel > $this->benchmarkLevel) {
			return false;
		}
		
		$scriptEnd = ($scriptEnd) ? 1 : 0;
		$endTime = microtime(true);
		$execTime = ($endTime - $this->startTime);

		$query = "INSERT INTO `benchmarking` (`bmPageId`, `bmPage`, `bmAction`, `bmExecTime`, `bmNotes`, `bmVars`, `bmScriptEnd`, `bmDate`, `bmTimeStart`, `bmTimeEnd`) VALUES (:pageId, :page, :action, :time, :notes, :vars, :scriptEnd, :date, :start, :end)";
		$dt = new DateTime('now', new DateTimeZone(DATE_DEFAULT_TIMEZONE));
		$params = array(
			'pageId' => $this->pageId,
			'page' => $this->page,
			'vars' => $this->vars,
			'action' => $action,
			'time' => $execTime,
			'notes' => $notes,
			'scriptEnd' => $scriptEnd,
			'start' => $this->startTime,
			'end' => $endTime,
			'date' => $dt->format(DATE_SQL_FORMAT)
		);
		
		try {
			return $GLOBALS['dbObj']->insert($query, $params);
		} catch (Exception $e) {
			return false;
		}
	}
};

?>