<?php
/**
 * Administer database connection
 *
 * @category   Core
 * @author     Kyle Knox <kyle.k@20xxproductions.com>
 * @copyright  2012 20xx Productions
 * @license    http://www.gnu.org/licenses/gpl.txt  GPLv3
 * @version    0.1.0
 * @since      Class available since Release 0.1.0
 */

class Database {

	/* MEMBERS */
	
	private $host;			// db_host
	private $name;			// db_name
	private $user;			// db_user
	private $password;		// db_password
	
	private $dbc;			// stores the database connection resource once it is established
	public $rowCount;		// stores the # of affected rows from the most recent query executed
	

	/* METHODS */
	
	public function __construct($host = null, $name = null, $user = null, $password = null) {
		$this->host = $host;
		$this->name = $name;
		$this->user = $user;
		$this->password = $password;
		
		$this->connect();
	}
	
	public function __get($var) {
		if ($var == 'rowCount') {
			return $this->$var;
		} else if ($var == 'errorInfo') {
			return $this->errorInfo();
		} else if ($var == 'errorCode') {
			return $this->errorCode();
		} else if ($var == 'lastInsertId') {
			return $this->dbc->lastInsertId();
		} else {
			return null;
		}
	}
	
	public function __set($var, $value) {
		return false;
	}
	
	/* QUERIES */
	
	// performs select query and returns the result set as an associative array (default)
	// if no results returned from query, returns an empty array
	public function select($query, $params = null, $fetch = PDO::FETCH_ASSOC) {
		if ($query == '') {
			return false;
		}

		try {
			$sth = $this->dbc->prepare($query);
			$sth->execute($params);
			$sth->setFetchMode($fetch);
			
			return $sth->fetchAll();
		} catch (PDOException $e) {
			SystemMessage::log(MSG_FATAL, 'Select query failed.', $this->exceptionDump($e, $sth));
			throw new Exception($e->getMessage());
			return array();
		}
	}
	
	// performs select query and returns a single result
	// returns null if no value found, or multiple results returned
	// the select can select anything (number, string, date, etc), but must by "SELECT X AS result"
	public function fetchResult($query, $params = null) {
		if ($query == '') {
			return false;
		}

		try {
			$sth = $this->dbc->prepare($query);
			$sth->execute($params);
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			$row = $sth->fetchAll();
		} catch (PDOException $e) {
			SystemMessage::log(MSG_FATAL, 'Select query failed.', $this->exceptionDump($e));
			throw new Exception($e->getMessage());
			return null;
		}
		
		if (is_array($row) && count($row == 1)) {
			return $row[0]['result'];
		} else {
			return null;
		}
	}
	
	// performs insert query
	// returns id of the inserted row if the table contains a primary key
	// or, returns true of the query was successful but there was not inserted id
	// or, returns false if the query was unsuccessful
	public function insert($query, $params = null) {
		if ($query == '') {
			return false;
		}
		
		$sth = $this->dbc->prepare($query);

		try {
			$st = $sth->execute($params);

			if ($st) {
				return ($this->dbc->lastInsertId() > 0) ? $this->dbc->lastInsertId() : true;
			} else {
				$eMsg = 'Error running insert query: ' . $query;
				
				SystemMessage::log(MSG_ERROR, $eMsg, array('query' => $query, 'params' => $params));
				throw new Exception($eMsg);
				return false;
			}
		} catch(PDOException $e) {
			SystemMessage::log(MSG_FATAL, 'Insert query failed.', $this->exceptionDump($e, $sth));
			throw new Exception($e->getMessage());
			return false;
		}
	}
	
	// performs update query
	// returns true and stores # updated rows in $this->rowCount if successful
	// or, returns false if unsuccessful
	public function update($query, $params = null) {
		if ($query == '') {
			return false;
		}
		
		$sth = $this->dbc->prepare($query);
		
		try {
			$sth->execute($params);
			$this->rowCount = $sth->rowCount();
			return true;
		} catch (PDOException $e) {
			$this->rowCount = 0;
			
			SystemMessage::log(MSG_FATAL, 'Update query failed.', $this->exceptionDump($e, $sth));
			throw new Exception($e->getMessage());
			return false;
		}
	}
	
	// performs delete query
	// returns true and stores # updated rows in $this->rowCount if successful
	// or, returns false if unsuccessful
	public function delete($query, $params = null) {
		if ($query == '') {
			return false;
		}
		
		$sth = $this->dbc->prepare($query);
		
		try {
			$sth->execute($params);
			$this->rowCount = $sth->rowCount();
			return true;
		} catch (PDOException $e) {
			$this->rowCount = 0;
			
			SystemMessage::log(MSG_FATAL, 'Delete query failed.', $this->exceptionDump($e, $sth));
			throw new Exception($e->getMessage());
			return false;
		}
	}
	
	/* TRANSACTIONS */
	
	// @todo - better transaction awareness/handling for these three functions
	// starts a db transaction
	public function beginTransaction() {
		return ($this->dbc->inTransaction()) ? $this->dbc->beginTransaction() : null;
	}
	
	// commits changes performed since the call of beginTransaction()
	public function commit() {
		return ($this->dbc->inTransaction()) ? $this->dbc->commit() : null;
	}
	
	// rollbak changes performed since the call of beginTransaction()
	public function rollBack() {
		//return ($this->dbc->inTransaction()) ? $this->dbc->rollBack() : false;
		return ($this->dbc->inTransaction()) ? $this->dbc->rollBack() : null;
	}
	
	/* UTILITIES */
	
	// establish connection to db and store in $this->dbc
	private function connect() {
		try {
			$this->dbc = new PDO('mysql:host='.$this->host.';dbname='.$this->name, $this->user, $this->password);
			return ($this->dbc) ? true : false;
		} catch (PDOException $e) {
			// if unable to connect to database, log fatal error in error.log and throw exception
			SystemMessage::log(3, 'DB connection failed.', $this->exceptionDump($e), 'file');
			throw new Exception($e->getMessage());
			return false;
		}
	}
	
	// returns string detailing system information at state of last query
	private function exceptionDump($e, $sth) {
		if (!$e) {
			return null;
		}
		
		$vars = array();
		
		$vars['message'] = $e->getMessage();
		$vars['exception-code'] = $e->getCode();
		$vars['file'] = $e->getFile();
		$vars['line'] = $e->getLine();
		$vars['trace'] = $e->getTrace();
		
		if ($sth) {
			ob_start();
			$sth->debugDumpParams();
			$sthDump = ob_get_contents();
			ob_end_clean();
		}
		
		$vars['statement'] = $sthDump;

		return print_r($vars, true);
	}
	
	public function isConnected() {
		return ($this->dbc) ? true : false;
	}
	
	// returns the errorCode associated to the last query
	public function errorCode() {
		return $this->dbc->errorCode();
	}
	
	// returns the errorInfo associated to the last query
	public function errorInfo() {
		return $this->dbc->errorInfo();
	}
	
	// prepares a string for non-prepared sql statements
	// surrounds in quotes and escapes any quotes in the string
	// called by makeDbSafe()
	public function quote($query) {
		return $this->dbc->quote($query);
	}
}
?>