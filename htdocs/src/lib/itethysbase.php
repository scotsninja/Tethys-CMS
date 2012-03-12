<?php
/**
 * Interface declaring global functions
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.9.0
 * @since      Class available since Release 0.9.0
 */

interface iTethysBase {

	/* Searching */
	
	// return an array of Objects
	public static function search(array $params = null, $perPage = 20, $pageNum = 1, &$totalResults = 0, $sort = null);
	
	// return single instance of Object
	public static function getById($id);
	
	// returns an array of the object id and object name
	public static function getList($sort = null, $showInactive = false);
	
	// returns a string of the object name
	public static function getNameById($id);
	
	/* Object Management */
	
	// returns true on removal of the object from the database and removal/deletion of any assocaited content and files
	public function delete();
	
	// returns true if the active user can perform the passed function on the object
	public function canEdit($function = null);
}

?>