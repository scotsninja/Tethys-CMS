<?php
/**
 * Handles logging and display of error/status messages
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.1.5
 * @since      Class available since Release 0.1.5
 */

class SystemMessage {

	/* MEMBERS */
	
	/* these values are stored in the settings table
	const MSG_SUCCESS = 0;		// operation was successful
	const MSG_WARNING = 1;		// user-level error (user tried to do something operation failed: forgot to fill-in a field)
	const MSG_ERROR = 2;		// user-level error (user tried to do something they shouldn't do)
	const MSG_FATAL = 3;		// system-level error (something went wrong with the code or server)
	const MSG_FATAL = 999;		// system-level message (used for logging debugging information)
	*/

	/* METHODS */
	
	// write message to log
	// $writeTo can be 'db', 'file', or 'both'
	public static function log($type = MSG_WARNING, $message = null, $vars = null, $writeTo = 'db') {
		if ($message == '') {
			return false;
		}
		
		$writeToFile = ($writeTo == 'file' || $writeTo == 'both');
		
		// write to database
		if ($writeTo == 'db' || $writeTo == 'both') {
			// check that db connection is open
			if ($GLOBALS['dbObj']) {
				$query = "INSERT INTO `message_log` (`mlType`, `mlValue`, `mlVars`, `mlSession`, `mlBacktrace`,  `mlDate`) VALUES (:type, :message, :vars, :session, :bt, :date)";
				$params = array(
					'type' => $type,
					'message' => $message,
					'vars' => print_r($vars, true),
					'session' => print_r($_SESSION, true),
					'bt' => print_r(debug_backtrace(), true),
					'date' => date(DATE_SQL_FORMAT)
				);
				
				$GLOBALS['dbObj']->insert($query, $params);
			} else {
				// if fails to write to db, write to error.log
				$writeToFile = true;
			}
		}
		
		// write to file
		if ($writeToFile) {
			$errorLog = (defined(CORE_ERROR_LOG)) ? CORE_ERROR_LOG : '../error.log';
			
			// open file for writing/appending
			if (file_exists($errorLog)) {
				$fh = fopen($errorLog, 'a');
			} // else, create file and open for writing
			else {
				$fh = fopen($errorLog, 'x');
			}

			if ($fh) {
				// date/type/message
				$line = date('Y-m-d H:i:s') . "|" . $type . "|" . $message."\n";
				
				fwrite($fh, $line);
				
				fclose($fh);
			}
		}
		
		// if a fatal error, alert admin(s)
		if ($type == MSG_FATAL) {
			$subject = 'Emergency: Karmastring Fatal Error';
			
			$to = (defined(CORE_WEBMASTER)) ? explode(',', CORE_WEBMASTER) : array('kyle.k@20xxproductions.com');
			
			$eMsg = "Fatal error occured:\n\n";
			$eMsg .= $message;
			
			// send email notification
			foreach ($to as $t) {
				@mail($t, $subject, $eMsg);
			}
		}
	}
	
	// saves a message into the queue for future output to screen
	// $name is used to group related messages
	public static function save($type = MSG_WARNING, $message = null, $name = '_') {
		if ($message == '') {
			return false;
		}
		
		$messageArr = array('type' => $type, 'value' => $message);

		$_SESSION['messages'][$name][] = $messageArr;
	}
	
	// outputs stored messages
	// if $name is passed, will output only messages associated to that name
	public static function output($name = '_', $block = true, $echo = true) {
		if ($name == '') {
			$name = '_';
		}
		
		$ret = '';

		if (is_array($_SESSION['messages']) && array_key_exists($name, $_SESSION['messages']) && is_array($_SESSION['messages'][$name])) {
			foreach ($_SESSION['messages'][$name] as $m) {
				$errClass = SystemMessage::getMessageClass($m['type']);
				$errTag = ($block) ? 'div' : 'span';
				
				$ret .= '<'.$errTag.' class="'.$errClass.'">'.$m['value'].'</'.$errTag.'>';
			}

			SystemMessage::clear($name);
		}
		
		if ($echo) {
			echo $ret;
		} else {
			return $ret;
		}
	}
	
	// clears stored messages
	public static function clear($name = null) {
		if ($name != '') {
			$_SESSION['messages'][$name] = array();
		} else {
			$_SESSION['messages'] = array();
		}
	}
	
	// returns the appropriate CSS class for the message type
	public static function getMessageClass($type = MSG_WARNING) {
		switch ($type) {
			case MSG_FATAL:
				$ret = 'msgFatal';
			break;
			case MSG_ERROR:
				$ret = 'msgError';
			break;
			case MSG_SUCCESS:
				$ret = 'msgSuccess';
			break;
			case MSG_WARNING:
			default:
				$ret = 'msgWarning';
			break;
		}
		
		return $ret;
	}
}

?>