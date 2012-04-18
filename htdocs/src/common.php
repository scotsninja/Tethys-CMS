<?php
/**
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2011 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.0.1
 * @since      Class available since Release 0.0.1
 */

$btStart = microtime(true);

// create output buffer
ob_start('ob_gzhandler');

// LOAD LIB CLASSES
spl_autoload_register();

// CONNECT TO DB
$execDir = explode('/', $_SERVER['PHP_SELF']);
$dirDepth = '';

for ($i = 0; $i < (count($execDir)-2); $i++) {
	$dirDepth .= '../';
}

// defines relative path so the inlcudes can always be accessed
define('CORE_DIR_DEPTH', $dirDepth);

$dbConfigFile =  (preg_match('/^localhost/i', $_SERVER['SERVER_NAME'])) ? CORE_DIR_DEPTH.'src/includes/db_config_xampp.php' : CORE_DIR_DEPTH.'src/includes/db_config.php';
require_once($dbConfigFile);
unset($dbConfigFile);

try {
	$dbObj = new Database($dbParams['host'], $dbParams['name'], $dbParams['user'], $dbParams['passwd']);
} catch (Exception $e) {
	header('Database connection error', true, 500);
	exit();
}

// LOAD/DEFINE CONSTANTS
try {
	loadConfig();
} catch (Exception $e) {
	SystemMessage::log(3, 'Error loading config: ' . $e->getMessage(), null, 'file');
	
	header('Could not load settings', true, 500);
	exit();
}

// INITIALIZE BENCHMARKING
$bmObj = new Benchmark(CORE_BENCHMARK_LEVEL, $btStart);

// INITIALIZE DATE OBJECT
$dtObj = new dtWrapper(DATE_DISPLAY_FORMAT_DATETIME, DATE_DEFAULT_TIMEZONE);
date_default_timezone_set(DATE_DEFAULT_TIMEZONE);

// INITIALIZE SESSION
session_start();

include_once($dirDepth.'src/includes/tethys_functions.php');
if (file_exists($dirDepth.'src/includes/local_functions.php')) {
	include_once($dirDepth.'src/includes/local_functions.php');
}

// check if user is logged in
$userObj = User::loadBySession();

/* FUNCTIONS */
// these functions are called before local_functions.php is loaded, so must be defined here

// loads the config file and stores values as constants
function loadConfig() {
	if (!$GLOBALS['dbObj']->isConnected()) {
		throw new Exception('Unable to connect to database.');
		return false;
	}
	
	$query = "SELECT `settingCategory` AS category, `settingName` AS name, `settingValue` AS value, `settingType` AS type, `settingDefault` AS def FROM `settings`";
	
	$settings = $GLOBALS['dbObj']->select($query);

	if (is_array($settings)) {
		foreach ($settings as $row) {
			// get constant name
			$settingName = $row['category'].' '.$row['name'];
			$settingName = strtoupper(str_replace(' ', '_', $settingName));
			
			// get constant value
			if (validateSetting($row['value'], $row['type'])) {
				if ($row['type'] == 'boolean') {
					$settingValue = ($row['value'] == 'true') ? true : false;
				} else {
					$settingValue = $row['value'];
				}
			} else if (validateSetting($default, $row['type'])) {
				if ($row['type'] == 'boolean') {
					$settingValue = ($row['def'] == 'true') ? true : false;
				} else {
					$settingValue = $row['def'];
				}
			} else {
				$settingValue = '';
			}

			// define constant
			if ($row['type'] == 'path' && strpos($_SERVER['PHP_SELF'], 'ajax') !== false) {
				$settingValue = CORE_DIR_DEPTH.$settingValue;
			}
			
			define($settingName, $settingValue);
		}
	}
}

// checks that the passed setting is the correct type
// returns true if is,
// otherwise returns false
function validateSetting($val, $type = 'string') {
	switch($type) {
		case 'email':
			$ret = isValidEmail($val);
		break;
		case 'integer':
		case 'decimal':
			$ret = is_numeric($val);
		break;
		case 'path':
			$ret = is_readable(CORE_DIR_DEPTH.$val);
		break;
		case 'boolean':
			$ret = (in_array($val, array('true', 'false'))) ? true : false;
		break;
		default:
		case 'string':
			$ret = true;
		break;
	}
	
	return $ret;
}

// returns true if the passed string is a valid email address
function isValidEmail($email = null) {
	if ($email == '') {
		return false;
	}
	
	$pattern = '/^(?:(?:(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|\x5c(?=[@,"\[\]\x5c\x00-\x20\x7f-\xff]))(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]\x5c\x00-\x20\x7f-\xff]|\x5c(?=[@,"\[\]\x5c\x00-\x20\x7f-\xff])|\.(?=[^\.])){1,62}(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]\x5c\x00-\x20\x7f-\xff])|[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]{1,2})|"(?:[^"]|(?<=\x5c)"){1,62}")@(?:(?!.{64})(?:[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.?|[a-zA-Z0-9]\.?)+\.(?:xn--[a-zA-Z0-9]+|[a-zA-Z]{2,6})|\[(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])(?:\.(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])){3}\])$/';
	
	return preg_match($pattern, $email);
}
