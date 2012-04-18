<?php
/**
 * Miscellaneous helper functions for validating data, performing db queries, or output.
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2011 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.8.0
 * @since      Class available since Release 0.0.1
 */

/* DATABASE FUNCTIONS */

// prepares a string for non-prepared sql statements
// surrounds in quotes and escapes any quotes in the string
function makeDbSafe($str) {
	return $GLOBALS['dbObj']->quote($str);
}

// adds the WHERE clause to an sql statement
function addQueryWhere($where = null) {
	$ret = '';
	
	if (is_array($where)) {
		$ret = ' WHERE ' . implode(" AND ", $where);
	}
	
	return $ret;
}

// adds the ORDER BY clause to an sql statement
function addQuerySort($sort = null) {
	return ($sort != '') ? ' ORDER BY ' . $sort : null;
}

// adds the LIMIT clause to an sql statement
function addQueryLimit($perPage = 20, $pageNum = 1) {
	if (!is_numeric($perPage) || $perPage < 1) {
		$perPage = 1;
	}
	if (!is_numeric($pageNum) || $pageNum < 1) {
		$pageNum = 1;
	}
	
	if ($perPage == 1 && $pageNum == 1) {
		$ret = " LIMIT 1";
	} else {
		$ret = " LIMIT " . ($perPage*($pageNum-1)) . ", " . $perPage;
	}
	
	return $ret;
}

/* VALIDATION FUNCTIONS */

// returns true if string is a valid IP address
function isValidIP($ip = null) {
	if ($ip == '') {
		return false;
	}
	
	$pattern = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';
	
	return preg_match($pattern, $ip);
}

// returns true if the string is valid password
function isValidPassword($passwd) {
	if ($passwd == '') {
		return false;
	}
	
	if (strlen($passwd) < 6) {
		return false;
	}
	
	return true;
}

// returns true if the $key is a valid and active access key
function isValidAccessKey($key) {
	if ($key == '') {
		return false;
	}
	
	$query = "SELECT `acActive` AS result FROM `access_keys` WHERE `acValue`=:key AND `acDateStart`<=NOW() AND `acDateEnd`>=NOW()";
	$params = array('key' => $key);

	$ret = $GLOBALS['dbObj']->fetchResult($query, $params);
	
	return ($ret == 1) ? true : false;
}

// returns true if the username/email has not been registered
function isUsernameAvailable($uname) {
	if ($uname == '') {
		return false;
	}
	
	$query = "SELECT `userId` AS result FROM `users` WHERE `userEmail`=:user";
	$params = array('user' => $uname);
	
	$row = $GLOBALS['dbObj']->fetchResult($query, $params);

	return ($row) ? false : true;
}

// returns true if the file was uploaded without error and is the correct type
function validateUpload($file, $type = null) {
	$ex = '';
	
	if (!is_array($file)) {
		throw new Exception('File not uploaded.');
		return false;
	}
	
	// check error code
	switch ($file['error']) {
		case 1:
			SystemMessage::log(MSG_WARNING, 'Uploaded file exceeds upload_max_filesize in php.ini');
			$eMsg = 'The uploaded file exceeds the max file size.';
		break;
		case 2:
			SystemMessage::log(MSG_WARNING, 'Uploaded file exceeds MAX_FILE_SIZE directive specified in form');
			$eMsg = 'The uploaded file exceeds the max file size.';
		break;
		case 3:
			SystemMessage::log(MSG_WARNING, 'The uploaded file was only partially uploaded');
			$eMsg = 'Error uploading file.';
		break;
		case 4:
			SystemMessage::log(MSG_WARNING, 'No file was uploaded');
			$eMsg = 'Error uploading file.';
		break;
		case 6:
			SystemMessage::log(MSG_WARNING, 'Missing a temporary folder');
			$eMsg = 'Error uploading file.';
		break;
		case 7:
			SystemMessage::log(MSG_WARNING, 'Failed to write file to disk');
			$eMsg = 'Error uploading file.';
		break;
		case 8:
			SystemMessage::log(MSG_WARNING, 'A PHP extension stopped the file upload');
			$eMsg = 'Error uploading file.';
		break;
		case 0:
		default:
			// do nothing
			$eMsg = '';
		break;
	}
	
	if ($eMsg != '') {
		throw new Exception($eMsg);
		return false;
	}
	
	// verify file size
	if ($file['size'] > CORE_MAX_UPLOAD_SIZE) {
		throw new Exception('File exceeds upload limit.');
		return false;
	}
	
	// verify file type
	if ($type == 'image') {
		$validMime = array('image/gif', 'image/jpeg', 'image/png');
	}
	
	$fileInfo = getimagesize($file['tmp_name']);
	
	if (!empty($fileInfo)) {
		if (!in_array($fileInfo['mime'], $validMime)) {
			throw new Exception('The uploaded file is not a supported image type.');
			return false;
		} else {
			return true;
		}
	} else {
		throw new Exception('The uploaded file is not a supported image type.');
	}
	
	return false;
}

/* UTILITY FUNCTIONS */

// outputs previous/next buttons and page information for result sets
// $url is the base url for links
// $pageVar is the GET parameter used to pass the page number
// $perPageVar is the GET parameter used to pass the number of results per page
function outputPagingLinks($url, $pageNum, $perPage, $totalResults = 0, $pageVar = 'page', $perPageVar = 'perpage') {
	if ($url == '') {
		return null;
	}
	
	if (!is_numeric($pageNum) || !is_numeric($perPage) || !is_numeric($totalResults)) {
		return null;
	}

	$lastPage = ceil($totalResults/$perPage);
	
	if ($pageNum < 1) {
		$pageNum = 1;
	} else if ($pageNum > $lastPage) {
		$pageNum = $lastPage;
	}
	
	$ret = '';
	
	if ($pageNum > 1) {
		$ret .= '<a class="pagination button button-blue" href="'.$url.'&'.$perPageVar.'='.$perPage.'&'.$pageVar.'=1"><< First</a>&nbsp;';
		$ret .= '<a class="pagination button button-blue" href="'.$url.'&'.$perPageVar.'='.$perPage.'&'.$pageVar.'='.($pageNum-1).'">< Previous</a>&nbsp;';
	}
	
	$ret .= '&nbsp;&nbsp;Page '.$pageNum.' of '.$lastPage.'&nbsp;&nbsp;';
	
	if ($pageNum < $lastPage) {
		$ret .= '<a class="pagination button button-blue" href="'.$url.'&'.$perPageVar.'='.$perPage.'&'.$pageVar.'='.($pageNum+1).'">Next ></a>&nbsp;';
		$ret .= '<a class="pagination button button-blue" href="'.$url.'&'.$perPageVar.'='.$perPage.'&'.$pageVar.'='.$lastPage.'">Last >></a>&nbsp;';
	}
	
	return $ret;
}

// outputs previous/next buttons and page information for result sets (uses javascript and ajax calls to update results)
// $func is the function to call to retrieve update results
// $pageNumField is the id of a hidden input that stores the current page number
function outputAjaxPagingLinks($func, $pageNum, $perPage, $totalResults = 0, $pageNumField = 'pagenum') {
	if ($func == '') {
		return null;
	}
	
	if (!is_numeric($pageNum) || !is_numeric($perPage) || !is_numeric($totalResults)) {
		return null;
	}
	
	$lastPage = ceil($totalResults/$perPage);
	
	if ($pageNum < 1) {
		$pageNum = 1;
	} else if ($pageNum > $lastPage) {
		$pageNum = $lastPage;
	}
	
	$ret = '';
	
	if ($pageNum > 1) {
		$ret .= '<a class="pagination button button-blue" href="javascript:void(0);" onclick="setPageNum(\''.$pageNumField.'\',1);'.$func.'();"><< First</a>&nbsp;';
		$ret .= '<a class="pagination button button-blue" href="javascript:void(0);" onclick="setPageNum(\''.$pageNumField.'\','.($pageNum-1).');'.$func.'();">< Previous</a>&nbsp;';
	}
	
	$ret .= '&nbsp;&nbsp;Page '.$pageNum.' of '.$lastPage.'&nbsp;&nbsp;';
	
	if ($pageNum < $lastPage) {
		$ret .= '<a class="pagination button button-blue" href="javascript:void(0);" onclick="setPageNum(\''.$pageNumField.'\','.($pageNum+1).');'.$func.'();">Next ></a>&nbsp;';
		$ret .= '<a class="pagination button button-blue" href="javascript:void(0);" onclick="setPageNum(\''.$pageNumField.'\','.($lastPage).');'.$func.'();">Last >></a>&nbsp;';
	}
	
	$ret .= '<div style="display:none;"><input type="hidden" id="'.$pageNumField.'" val="'.$pageNum.'" /></div>';
	
	return $ret;
}

// @todo - displaySteps
function outputSteppedPagingLinks($url, $pageNum, $perPage, $totalResults = 0, $displaySteps = 0, $pageVar = 'page', $perPageVar = 'perpage', $onClick = null) {
	if ($url == '') {
		return null;
	}
	
	if (!is_numeric($pageNum) || !is_numeric($perPage) || !is_numeric($totalResults)) {
		return null;
	}

	$lastPage = ceil($totalResults/$perPage);
	
	if ($pageNum < 1) {
		$pageNum = 1;
	} else if ($pageNum > $lastPage) {
		$pageNum = $lastPage;
	}
	
	$ret = '';
	
	for($i = 1; $i<=$lastPage;$i++) {
		$pUrl = ($url == '#') ? $url : $url.'&'.$perPageVar.'='.$perPage.'&'.$pageVar.'='.$i;
		$classes = array('pagination');
		if ($i == $pageNum) {
			$classes[] = 'pagination-current';
		}
		
		if ($onClick != '') {
			$pOnClick = ' onclick="'.str_replace(array('#page', '#pageVar', '#perPageVar'), array($i, $pageVar, $perPage, $perPageVar), $onClick).'"';
		}
		
		$ret .= '<a href="'.$pUrl.'" class="'.implode(' ', $classes).'"'.$pOnClick.'>'.$i.'</a>';
	}
	
	return $ret;
}

// converts the GET parameters from the url into a string
function addQueryString($ignore = null) {
	if (!is_array($ignore)) {
		$ignore = ($ignore != '') ? array($ignore) : array();
	}
	
	foreach ($_GET as $key => $value) {
		if ($value != '' && !in_array($key, $ignore)) {
			$ret .= $key.'='.$value.'&';
		}
	}
	
	return $ret;
}

// returns a ckEditor using the javascript api
function outputCkEditor($name, $value = null,  $toolbar = 'Simple') {
	if ($name == '') {
		return null;
	}
	
	$ckId = 'ck_'.$name;
	
	$ret = '<textarea class="ckeditor" rows="5" cols="40" id="'.$ckId.'" name="'.$name.'">'.$value.'</textarea>';
	
	$GLOBALS['includes']['js'] .= '<script type="text/javascript">
		$(document).ready(function() {
			CKEDITOR.replace(\''.$ckId.'\', {
				toolbar: \''.$toolbar.'\'
			});
		});
	</script>';
	
	return $ret;
}

// returns Disqus comment thread
function outputDisqus() {
	$shortname = DISQUS_SHORTNAME;
	$developerMode = (CORE_DEVELOPMENT) ? 1 : 0;
	$identifier = $GLOBALS['dIdentifier'];
	$url = $GLOBALS['dUrl'];
	
	$ret = '<div id="disqus_thread"></div>
	<script type="text/javascript">
		var disqus_shortname = \''.$shortname.'\';
		var disqus_developer = \''.$developerMode.'\';
		var disqus_identifier = \''.$identifier.'\';
		var disqus_url = \''.$url.'\';

		(function() {
			var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;
			dsq.src = \'http://\' + disqus_shortname + \'.disqus.com/embed.js\';
			(document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);
		})();
	</script>
	<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
	<div class="dq_powered_by"><a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a></div>';
	
	return $ret;
}

function getDisqusIdentifier($url) {
	if ($url == '') {
		return null;
	}

	return substr(md5($url), 0, 10);
}

function outputDisqusCommentCount($url = null) {
	if ($url == '') {	
		return null;
	}
	
	return '<a href="'. $url .'#disqus_thread" data-disqus-identifier="'.getDisqusIdentifier($url).'"></a>';
}

function outputDisqusCommentCountScript() {
	$shortname = DISQUS_SHORTNAME;

	$GLOBALS['includes']['js'] .= '<script type="text/javascript">
		var disqus_shortname = "'.$shortname.'";

		(function () {
			var s = document.createElement("script"); s.async = true;
			s.type = "text/javascript";
			s.src = "http://" + disqus_shortname + ".disqus.com/count.js";
			(document.getElementsByTagName("HEAD")[0] || document.getElementsByTagName("BODY")[0]).appendChild(s);
		}());
	</script>';
}

function outputSharingLinks($include = null) {
	if ($include == '') {
		$include = array('twitter', 'google', 'facebook');
	}
	
	$ret = '';
	
	foreach ($include as $i) {	
		switch($i) {
			case 'share':
				$ret .= '<span class="st_sharethis_hcount" displayText="ShareThis"></span>';
			break;
			case 'facebook':
				$ret .= '<span class="st_fblike_hcount" displayText="Facebook Like"></span>';
			break;
			case 'twitter':
				$ret .= '<span class="st_twitter_hcount" displayText="Tweet"></span>';
			break;
			case 'google':
				$ret .= '<span class="st_plusone_hcount" displayText="Google +1"></span>';
			break;
			case 'reddit':
				$ret .= '<span class="st_reddit_hcount" displayText="Reddit"></span>';
			break;
			case 'pinterest':
				$ret .= '<span class="st_pinterest_hcount" displayText="Pinterest"></span>';
			break;
			case 'linkedin':
				$ret .= '<span class="st_linkedin_hcount" displayText="LinkedIn"></span>';
			break;
			case 'digg':
				$ret .= '<span class="st_digg_hcount" displayText="Digg"></span>';
			break;
			case 'email':
				$ret .= '<span class="st_email_hcount" displayText="Email"></span>';
			break;
		}
	}
	
	$ret .= '<script type="text/javascript">var switchTo5x=true;</script>
	<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
	<script type="text/javascript">stLight.options({publisher: "ur-dacf1bd-da6b-dafd-cef9-4224acc07039"}); </script>';
	
	return $ret;
}

// output a date/time picker using jquery-ui
function outputDatePicker($name, $value = null, $includeNull = false, $type = 'date') {
	if ($name == '') {
		return;
	}
	
	$id = 'dt_'.$name;
	
	$ret = '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" size="15" />';
	
	if ($includeNull) {
		$ret .= ' <input type="checkbox" name="'.$name.'Null" value="1" onclick="if($(this).attr(\'checked\')) { $(\'#'.$id.'\').val(\'\'); }" />';
	}
	
	$GLOBALS['includes']['js'] .= '<script type="text/javascript">
		$(function() {
			$("#'.$id.'").datepicker({
				showButtonPanel: true,
				dateFormat: "yy-mm-dd",
				changeMonth: true,
				changeYear: true,
				showOtherMonths: true
			});
		});
	</script>';
	
	return $ret;
}

// takes a time string and formats to 24-hour time
function formatTime($time) {
	if (strlen($time) < 3) {
		return false;
	}
	
	$time = str_replace(array(':', ' '), '', $time);
	$ret = null;
	
	// determine if am or pm
	if (preg_match('/a|p/i', $time)) {
		$meridiem = (preg_match('/p/i', $time)) ? 'pm' : 'am';
		
		// strip all non-numeric characters
		$time = preg_replace('/[a-zA-Z]*/', '', $time);
		
		if (strlen($time) == 3) {
			$hours = substr($time, 0, 1);
			$minutes = substr($time, 1);
		} else {
			$hours = substr($time, 0, 2);
			$minutes = substr($time, 2);
		}
		
		if ($hours == 12) {
			$hours = ($meridiem == 'pm') ? 23 : 0;
		} else if ($meridiem == 'pm') {
			$hours += 12;
		}
		if ($minutes < 0 || $minutes > 59) {
			return false;
		}
		
		$ret = str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT);
	} // else, 24-hour format
	else {
		if (strlen($time) == 3) {
			$hours = substr($time, 0, 1);
			$minutes = substr($time, 1);
		} else {
			$hours = substr($time, 0, 2);
			$minutes = substr($time, 2);
		}
		
		if ($hours < 0 || $hours > 23) {
			return false;
		}
		if ($minutes < 0 || $minutes > 59) {
			return false;
		}
		
		$ret = str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT);
	}

	return $ret;
}

// removes spaces and url-encodes file names and paths
function makeFileSafe($str) {
	$str = str_replace(' ', '_', $str);
	
	return urlencode($str);
}

// returns the encrypted string
function encryptPassword($passwd) {
	return md5($passwd);
}

// mail wrapper function
// $to can be a simple string or an array, containing multiple too addresses
// if the $message contains html tags, the function add the required headers to the email
function sendMail($to, $subject, $message, $headersArr = null) {
	if ($to == '' || $subject == '' || $message == '') {
		return false;
	}
	
	if (!is_array($to)) {
		$toArr[] = $to;
	} else {
		$toArr = $to;
	}
	
	$headers = '';
	
	// check if there are html tags in the message
	if (strlen($message) > strlen(strip_tags($message))) {
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	}

	// loop through the headers and add each
	if (is_array($headersArr)) {
		foreach ($headersArr as $header) {
			$headers .= $header . "\r\n";
		}
	}
	
	if (!strpos($headers, 'From:')) {
		$headers .= "From: no-reply@20xxproductions.com \r\n";
	}
	
	$headers .= 'X-Mailer: PHP/' . phpversion();
	
	foreach ($toArr as $t) {
		$ret[] = mail($t, $subject, $message, $headers);
	}
	
	return (!in_array(false, $ret));
}

// moves an upload image to the proper directory
function uploadImage($type, $id, $image) {
	if ($type == '' || !is_numeric($id)) {
		return false;
	}
	
	try {
		validateUpload($image, 'image');
	} catch (Exception $e) {
		throw new Exception($e->getMessage());
	}
	
	$uploadDir = 'img/'.strtolower($type).'s/';
	
	if (!file_exists($uploadDir)) {
		@mkdir($uploadDir);
	}
	
	$uploadDir .= $id.'/';
	
	if (!file_exists($uploadDir)) {
		@mkdir($uploadDir);
	}
	
	$fileName = makeFileSafe($image['name']);
	
	if (file_exists($uploadDir.$fileName)) {
		$fileNameBase = explode('.', $fileName);
		$s = 1;
		while (file_exists($uploadDir.$fileName)) {
			$fileName = $fileNameBase[0] .'_'.$s++ . '.'. $fileNameBase[1];
		}
	}
	
	if (move_uploaded_file($image['tmp_name'], $uploadDir.$fileName)) {
		return $fileName;
	} else {
		throw new Exception('Error uploading file.');
	}
}

/* MISC */

// stores the page view in the db
function recordPageView() {
	$userId = (User::isLoggedIn()) ? $GLOBALS['userObj']->id : 0;
	
	$query = "INSERT INTO `page_views` (`pvUserId`, `pvIPAddress`, `pvPage`, `pvReferringPage`, `pvClient`, `pvDate`) VALUES (:userId, :ip, :page, :referringPage, :client, :date)";
	
	$params = array(
		'userId' => $userId,
		'ip' => $_SERVER['REMOTE_ADDR'],
		'page' => $_GET['url'],
		'referringPage' => $_SERVER['HTTP_REFERER'],
		'client' => $_SERVER['HTTP_USER_AGENT'],
		'date' => $GLOBALS['dtObj']->format('now', DATE_SQL_FORMAT)
	);
	
	return $GLOBALS['dbObj']->insert($query, $params);
}

?>