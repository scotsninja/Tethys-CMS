<?php

class dtWrapper {

	private $dtObj;
	private $format;
	private $timezone;
	
	private $errors;
	private $last;
	
	public function __construct($format = null, $timezone = null) {
		$this->format = ($format) ? $format : DATE_DISPLAY_FORMAT_DATETIME;
		$this->timezone = ($timezone) ? $timezone : DATE_DEFAULT_TIMEZONE;
	}
	
	public function __get($var) {
		return ($this->$var) ? $this->$var : null;
	}
	
	public function __set($var, $val) {
		switch ($var) {
			case 'format':
				$this->$var = ($val) ? $val : DATE_DISPLAY_FORMAT_DATETIME;
			break;
			case 'timezone':
				$this->$var = ($val) ? $val : DATE_DEFAULT_TIMEZONE;
				if ($this->dtObj instanceof DateTime) {
					$this->dtObj->setTimeZone(new DateTimeZone($this->timezone));
				}
			break;
			default:
			break;
		}
	}
	
	/* FORMATTING */
	
	// returns a formatted date string
	public function format($date, $format = null) {
		$tf = ($format) ? $format : $this->format;
		
		try {
			$this->dtObj = $this->toDT($date);
			
			if ($this->dtObj instanceof DateTime) {
				$this->last = $this->dtObj->format($tf);
				return $this->last;
			} else {
				throw new Exception('Invalid date object');
			}
		} catch (Exception $e) {
			$this->errors = $this->getLastErrors();
		}
	}
	
	// returns the last 
	public function last() {
		return $this->last;
	}

	// returns timezone offset of last date
	public function getOffset() {
		return ($this->dtObj instanceof DateTime) ? $this->dtObj->getOffset() : null;
	}
	
	/* COMPARISON */
	
	// add a date interval to the passed date and output
	public function add($date, $add, $format = null) {
		$tf = ($format) ? $format : $this->format;
		
		try {
			$this->dtObj = $this->toDT($date);
			
			if ($this->dtObj instanceof DateTime) {
				$this->dtObj->add(new DateInterval($add));
				$this->last = $this->dtObj->format($tf);
				return $this->last;
			} else {
				throw new Exception('Invalid date object');
			}
		} catch (Exception $e) {
			$this->errors = $this->getLastErrors();
		}
	}
	
	// subtract a date interval from the passed date and output
	public function subtract($date, $sub, $format = null) {
		$tf = ($format) ? $format : $this->format;
		
		try {
			$this->dtObj = $this->toDT($date);
			
			if ($this->dtObj instanceof DateTime) {
				$this->dtObj->sub(new DateInterval($sub));
				$this->last = $this->dtObj->format($tf);
				return $this->last;
			} else {
				throw new Exception('Invalid date object');
			}
		} catch (Exception $e) {
			$this->errors = $this->getLastErrors();
		}
	}
	
	// returns difference between date1 and date2
	// format is a valid DateInterval format
	public function diff($date1, $date2, $format = '%r%a') {
		try {
			$d1 = $this->toDt($date1);
			$d2 = $this->toDt($date2);
			
			$int = $d1->diff($d2);
			return $int->format($format);
		} catch (Exception $e) {
			$this->errors = $this->getLastErrors();
		}
	}
	
	// compares the two dates
	// returns -1, if date1 is less than date2
	// returns 0, if the dates are equal
	// returns 1, if date1 is greater than date2
	public function comp($date1, $date2) {
		try {
			$d1 = $this->toDt($date1);
			$d2 = $this->toDt($date2);
			
			if ($d1 == $d2) {
				$ret = 0;
			} else if ($d1 > $d2) {
				$ret = 1;
			} else {
				$ret = -1;
			}
			
			return $ret;
		} catch (Exception $e) {
			$this->errors = $this->getLastErrors();
		}
	}
	
	/* UTILITIES */
	
	// return number of days in the specified month
	// if no month or year set, defaults to current month/year
	public static function getDaysInMonth($month = null, $year = null) {
		$month = (is_numeric($month) && $month >= 1 && $month <= 12) ? $month : date('m');
		$year = (is_numeric($year) && $year > 0) ? $year : date('Y');
		
		switch ($month) {
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				$ret = 31;
			break;
			case 4:
			case 6:
			case 9:
			case 11:
				$ret = 30;
			break;
			case 2:
				$ret = (dtWrapper::isLeapYear($year)) ? 29 : 28;
			break;
			default:
				$ret = 0;
			break;
		}
		
		return $ret;
	}
	
	// returns true if the passed year is a leap year
	// defaults to current year
	public static function isLeapYear($year = null) {
		if (!is_numeric($year) || $year < 1) {
			$year = date('Y');
		}

		if ($year%4 == 0) {
			if ($year%100 == 0) {
				$ret = ($year%400 == 0);
			} else {
				$ret = true;
			}
		} else {
			$ret = false;
		}

		return $ret;
	}
	
	// returns errors constructing last DateTime object
	public function getLastErrors() {
		return DateTime::getLastErrors();
	}
	
	// convert a date to a DateTime object
	private function toDT($date) {
		if (is_numeric($date) && $date > 9999) {
			$ret = $this->fromTimestamp($date);
		} else if ($date instanceof DateTime) {
			$ret = $this->fromDT($date);
		} else {
			$ret = $this->fromString($date);
		}

		return $ret;
	}
	
	// adjust a DateTime object to the default timezone, and store it
	private function fromDT($date) {
		if ($date instanceof DateTime) {
			$date->setTimezone(new DateTimeZone($this->timezone));
			return $date;
		} else {
			throw new Exception('Invalid value. Expecting DateTime object.');
			return false;
		}
	}
	
	// create a DateTime object from a timestamp and store it
	private function fromTimestamp($date) {
		if (is_numeric($date) && $date > 0) {
			return new DateTime('@'.$date, new DateTimeZone($this->timezone));
		} else {
			throw new Exception('Invalid value. Expecting timestamp.');
			return false;
		}
	}
	
	// create a DateTime object from a date string and store it
	private function fromString($date) {
		if ($date == '' || is_string($date)) {
			return new DateTime($date, new DateTimeZone($this->timezone));
		} else {
			throw new Exception('Invalid value. Expecting string.');
			return false;
		}
	}
}

?>