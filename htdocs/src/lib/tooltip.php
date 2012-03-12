<?php
/**
 * Display custom tooltips
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.4.0
 * @since      Class available since Release 0.4.0
 */

class Tooltip {

	/* MEMBERS */
	
	private $value;		// the tooltip text
	private $icon;		// icon to use; options are (alert, help, info)
	private $id;		// the id of the <a> tag containing the tooltip
	
	
	/* METHODS */
	
	public function __construct($value = null, $icon = null, $id = null) {
		$this->value = $value;
		$this->icon = $icon;
		$this->id = $id;
	}
	
	// returns html string that will generate tooltip
	public function output() {
		$ret = '';
		
		$ret .= '<a href="javascript:void(0);" class="tooltip" title="'.addslashes($this->value).'"';
		
		if ($this->id != '') {
			$ret .= ' id="'.$this->id.'"';
		}
		
		$ret .= '>';
		
		if ($this->validateIcon()) {
			$icon = $this->icon.'.png';
		} else {
			$icon = 'help.png';
		}
		
		$ret .= '<img alt="[?]" src="/'.CORE_ICON_PATH.$icon.'" />';
		$ret .= '</a>';
		
		return $ret;
	}
	
	// returns html string for an alert-icon tooltip
	public static function outputAlert($msg = null) {
		if ($msg == '') {
			return null;
		}
		
		$icon = 'alert.png';
		$ret = '';
		
		$ret .= '<a href="javascript:void(0);" title="'.addslashes($msg).'" class="tooltip">';
		$ret .= '<img alt="[?]" src="/'.CORE_ICON_PATH.$icon.'" />';
		$ret .= '</a>';
		
		return $ret;
	}
	
	// returns html string for an help-icon tooltip
	public static function outputHelp($msg = null) {
		if ($msg == '') {
			return null;
		}
		
		$icon = 'help.png';
		$ret = '';
		
		$ret .= '<a href="javascript:void(0);" title="'.addslashes($msg).'" class="tooltip">';
		$ret .= '<img alt="[?]" src="/'.CORE_ICON_PATH.$icon.'" />';
		$ret .= '</a>';
		
		return $ret;
	}
	
	// returns html string for an info-icon tooltip
	public static function outputInfo($msg = null) {
		if ($msg == '') {
			return null;
		}

		$icon = 'info.png';
		$ret = '';
		$ret .= '<a href="javascript:void(0);" title="'.addslashes($msg).'" class="tooltip">';
		$ret .= '<img alt="[?]" src="/'.CORE_ICON_PATH.$icon.'" />';
		$ret .= '</a>';
		
		return $ret;
	}
	
	// returns true if the icon is valid icon-type
	private function validateIcon() {
		$validTypes = array('alert', 'help', 'info');
		$path = CORE_ICON_PATH.$this->icon.'.png';
		
		return (in_array($this->icon, $validTypes) && is_file($path));
	}
}

?>