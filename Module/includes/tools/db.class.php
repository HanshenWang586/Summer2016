<?php

class DbTools extends CMS_Class {
	private $queryList = array();
	private $errorList = array();
	private $errnoList = array();
	private $runningTimeList = array();
	private $runSelect = array();
	private $connectionData;
	
	/**
	 * @var mysql_resource The database connection
	 */
	public $link;
	
	public function init($data) {
		if (is_array($data)) $this->connect($data);
	}
	
	/*
	 *	Connects to the database.
	 *	Dies and gives an error message on no database or no connection.
	 *
	 *	@param array	data			An optionalparameter with the login data.
	 *	@param boolean	forceReconnect	Force to reconnect with current connection info
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function connect($data, $forceReconnect = false) {
		if ((!$this->link || $forceReconnect) && $data) {
			$this->connectionData = $data;
			if ($this->link) $this->disconnect();
			$errorReporting = error_reporting(E_NONE);
			if ($this->link = mysql_connect($data['server'], $data['user'], $data['password'], true)) {
				$this->run_query("SET NAMES 'utf8'");
				$this->select_db($data['name']);
				
			} else {
				include_once('oops.html');
				die();
			}
			error_reporting($errorReporting);
		}
	}
	
	public function select_db($db) {
		if ($this->link) {
			$result = mysql_select_db($db, $this->link);
			if (!$result) echo "No database selected!";
			else return true;
		} else echo "No connection available";
		return false;
	}
	
	/*
	 *	Disconnects the current connection.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function disconnect() {
		@mysql_close($this->link);
	}
	
	public function transaction() {
		return $this->run_query('START TRANSACTION');
	}
	
	public function commit() {
		return $this->run_query('COMMIT');
	}
	
	public function rollback() {
		return $this->run_query('ROLLBACK');
	}
	
	/**
	 * Inserts or replaces an record into the database
	 *
	 * @param string $table Name of the table
	 * @param array $rows assoc array( 'rowName' => 'value') or:
	 *                          array(array('rowName' => 'value'), 'rowName' => 'value'))
	 * @param opt[string] $type Either REPLACE or INSERT
	 * @return mixed When a single record is inserted/replaced the last_insert_id is retured.
	 * 				 When multiple rows are inserted the affected rows are returned.
	 * 				 When a error occures (bool) false is returned.
	 */
	private function _insert($table, $rows, $type = false, $options = array()) {
		if (!$type || strtoupper($type) != 'REPLACE') $type = 'INSERT';
		if (request($options['ignore'])) $type .= " IGNORE";
		$query = sprintf("%s INTO %s", $type, $table);
	
		// If an index number is set, we have an array of arrays (otherwise the programmer doesn't know
		// what he's doing.
		if ($multiple = isset($rows[0])) {
			$keys = array_keys($rows[0]);
		} else {
			$keys = array_keys($rows);
			$rows = array($rows);
		}
		$query .= " ("
			. array_implode_map(",", $keys, "backquote")
			. ") VALUES ";
		$first = true;
		$count = count($rows);
		for ($i = 0; $i < $count; $i++) {
			if ($i > 0) $query .= ",";
			$query	.= "(" . array_implode_map(",", array_values($rows[$i]), array($this, 'quote')) . ")";
		}
	
		if (request($options['update'])) $query .= ' ON DUPLICATE KEY UPDATE ' . $options['update'];
		
		// Run the query.. if the query is succesful, see what happened
		if ($this->run_query($query)) {
			// If we have multiple rows to insert, return the number of affected rows
			if ($multiple) return $this->affected_rows();
			else {
				// If only one row, get the insert id
				$return = $this->insert_id();
				// If there's not an insert id (no primary auto increment key?) return true, as the query succeeded
				if (!$return) return true;
				else return $return;
			}
		}
		// if we got this far, we failed. Return false.
		return false;
	}
	
	public function update($table, $clauses, $fields) {
		// If the causes are not set, we're not updating anything. An update on ALL entries needs an explicit clause, for
		// security reasons. Hereby we eliminate the risk of accidently updating all fields in a table.
		if (!$clauses) return false;
		$query = sprintf("UPDATE %s SET ", $table);
		$first = true;
		foreach ($fields as $field => $value) {
			if (!$first) {
				$query .= ", ";
			} else {
				$first = false;
			}
			$query .= sprintf("`%s`=%s", $field, $this->quote($value));
		}
		$query .= $this->build_clauses($clauses);
		return $this->run_query($query) ? $this->affected_rows() : false;
	}
	
	
	/**
	 * @param unknown_type $table
	 * @param unknown_type $rows
	 * @return unknown
	 */
	public function replace($table, $rows, $options = array()) {
		return $this->_insert($table, $rows, 'REPLACE', $options);
	}
	
	/**
	 * @param unknown_type $table
	 * @param unknown_type $rows
	 * @return unknown
	 */
	public function insert($table, $rows, $options = array()) {
		return $this->_insert($table, $rows, false, $options);
	}
	
	public function count($table, $clauses = false) {
		$query = sprintf("SELECT count(*) as number FROM `%s`", $table);
		if ($clauses) $query .= $this->build_clauses($clauses);
		if ($result = $this->run_select($query, true)) return $result['number'];
		return 0;
	}
	
	/**
	 * Selects data from a database,
	 *
	 * @param string $table Database table to query
	 * @param unknown_type $clauses
	 * @param array $params Fields to query set as
	 * 				array(
	 * 					getFields => array('field_1', 'field_2'),
	 * 					modifier => 'WHERE?',
	 * 					having => 'having params',
	 * 					order => 'DESC',
	 * 					orderBy => 'fieldsname',
	 * 					singleResult => 'Return value on q error??'
	 * @return mixed Query result.
	 */
	public function query($table, $clauses = false, $params = array()) {
		// See if we only need to get one row in the result. Automatically set in certain cases.
		$singleResult = (is_numeric($clauses) || request($params['singleResult']) || isset($params['selectField'])) && !request($params['returnArray']);
		if ($singleResult) $params['limit'] = 1;
		$query = $this->get_query($table, $clauses, $params);
		return $this->run_select($query, $singleResult, $params);
	}
	
	private function getTransposeFields($transpose) {
		if (is_string($transpose)) return array($transpose);
		elseif (
			array_key_exists('transpose', $transpose) &&
			is_array($transpose['transpose']) && 
			(
				(array_key_exists('selectKey', $transpose['transpose']) and (
					!array_key_exists('selectValue', $transpose['transpose']) or is_string($transpose['transpose']['selectValue']))
				) or
				(is_vector($transpose['transpose']) and count($transpose['transpose']) == 2 and $transpose['transpose'][1] !== true)
			)
		) {
			return array_values($transpose['transpose']);
		} else return NULL;
	}
	
	public function get_query($table, $clauses = false, $params = array()) {
		// If transpose is set with key / value selection, those are the only values we have to obtain from the DB
		if (isset($params['getFields'])) $getFields = $params['getFields'];
		elseif (isset($params['selectField'])) $getFields = $params['selectField'];
		elseif ($transpose = request($params['transpose'])) $getFields = $this->getTransposeFields($params['transpose']);
		if (!isset($getFields)) $getFields = sprintf('%s.*', $table);
		if (is_array($getFields)) $getFields = $table . '.`' . implode('`, ' . $table . '.`', $getFields) . '`';
		
		// we will use these clauses in case we get more clauses from the joins, to concat
		$temp = $this->build_clauses($clauses, true, false, $table);
		$_clauses = $temp ? array($temp) : array();
		
		$joinText = '';
		if ($join = request($params['join'])) {
			if (is_vector($params['join'])) { 
				foreach ($params['join'] as $join) $joinText .= $this->build_join($table, $join, $_clauses, $getFields);
			}
			else $joinText .= $this->build_join($table, $params['join'], $_clauses, $getFields);
		}
		
		$query = sprintf("SELECT %s %s FROM %s", request($params['modifier']), $getFields, $table);
		$query .= $joinText;
		
		if ($_clauses) $query .= ' WHERE' . (count($_clauses) > 1 ? implode(' AND ', $_clauses) : array_pop($_clauses));
		
		if (isset($params['groupBy'])) $query .= " GROUP BY " . $params['groupBy'];
		
		if (isset($params['having'])) $query .= " HAVING " . $this->build_clauses($params['having'], true);
		
		if ($orderBy = array_get_set($params, array('orderBy','orderby'), false)) {
			if ($orderBy == "rand()") {
				$temp = $orderBy;
			} else {
				$order = request($params['order']);
				if (strtoupper($order) != "DESC") $order = 'ASC';
				$temp = sprintf("%s %s", $orderBy, $order);
			}
			$query .= sprintf(" ORDER BY %s", $temp);
		}
		if ($limit = request($params['limit'])) {
			$query .= sprintf(" LIMIT %d OFFSET %d", $limit, request($params['offset']));
		}
		return $query;
	}
	
	private function build_join($table, array $join, array &$clauses = array(), &$getFields) {
		// Use an alias?
		if (isset($join['joinTable'])) $table = $join['joinTable'];
		if (isset($join['alias'])) {
			$joinTable = sprintf('`%s` AS `%s`', $join['table'], $join['alias']);
			$alias = $join['alias'];
		} else $joinTable = $alias = $join['table'];
		
		if (request($join['where'])) {
			$clauses[] = $this->build_clauses($join['where'], true, false, $alias);
			// If there are further clauses, make sure we get the syntax right
		}
		
		if (isset($join['fields'])) {
			$getFields .= ',' . (is_array($join['fields']) ? $alias . '.`' . implode('`, ' . $alias . '.`', $join['fields']) . '`' : ($join['fields'] == '*' ? $alias . '.' . $join['fields'] : $join['fields']));
		}
		
		$joinLeft = strpos($join['on'][0], '.') > -1 ? $join['on'][0] : sprintf('`%s`.`%s`', $table, $join['on'][0]);
		
		return sprintf(' %s JOIN %s ON (%s = `%s`.`%s`)', 
			isset($join['type']) ? strtoupper($join['type']) : 'LEFT',
			$joinTable,
			$joinLeft,
			$alias,
			$join['on'][1]
		);
	}
	
	// Simply execute a query and nicely return the result.
	public function run_select($query, $singleResult = false, $params = array()) {		
		if (!$this->link) return false;
		if ($this->model->debug()) $time = microtime_float();
		$result = $this->run_query($query);
		$return = array();

		$transpose = request($params['transpose']);
		$selectValue = $selectKey = false;
		if ($transpose) {
			if (is_string($transpose)) $selectKey = $transpose;
			elseif (is_array($transpose)) {
				if (is_vector($transpose) and count($transpose) == 2) {
					$selectKey = $transpose[0];
					$selectValue = $transpose[1];
				} else {
					$selectKey = request($transpose['selectKey']);
					$selectValue = request($transpose['selectValue']);
				}
			}
		}
		
		$groupBy = request($params['arrayGroupBy']);
		$callback = request($params['callback']);
		
		if ($result && $this->num_rows($result) > 0) {
			if ($singleResult) {
				$return = $this->fetch($result);
				if ($callback) $return = call_user_func($callback, $return);
				if ($field = request($params['selectField'])) {
					if (array_key_exists($field, $return)) $return = $return[$field];
				}
			}
			else while($row = $this->fetch($result)) {
				// Run the callback first over the result
				if ($callback) $row = call_user_func($callback, $row);
				// Then see if we need to group the result
				if ($groupBy && array_key_exists($groupBy, $row)) {
					$groupKey = $row[$groupBy];
					if ($groupKey === NULL) $groupKey = 'NULL';
				} else $groupKey = false;
				// Now check if we wish to transpose the result
				if ($transpose === true) {
					foreach($row as $key => $value)	$groupKey !== false ? $return[$groupKey][$key][] = $value : $return[$key][] = $value;
				} elseif ($transpose and $selectKey and array_key_exists($selectKey, $row)) {
					if ($selectValue) {
					 	if ($selectValue === true) $groupKey !== false ? $return[$groupKey][$row[$selectKey]] = $row : $return[$row[$selectKey]] = $row;
						elseif (isset($row[$selectValue])) $groupKey !== false ? $return[$groupKey][$row[$selectKey]] = $row[$selectValue] : $return[$row[$selectKey]] = $row[$selectValue];
					} elseif(request($row[$selectKey])) $groupKey !== false ? $return[$groupKey][] = $row[$selectKey] : $return[] = $row[$selectKey];
				} else $groupKey !== false ? $return[$groupKey][] = $row : $return[] = $row;
			}
 		}
		
 		if ($this->model->debug() && strpos($query, 'EXPLAIN') === false) {
			$this->runSelect[] = array(
				'query' => $query,
				'runtime' => microtime_float() - $time,
				'querytime' => $this->getRunningTime(),
				'scripttime' => microtime_float() - $time - $this->getRunningTime(),
				'transpose' => $params['transpose'],
				'arrayGroupBy' => $params['arrayGroupBy'],
				'callback' => $params['callback'] ? true : false,
				'explain' => ($this->model->options['db_explain'] and strpos($query, 'EXPLAIN') === false) ? $this->run_select('EXPLAIN EXTENDED ' . $query) : false
			);
		}
		return $return;
	}
	
	public function delete($table, $clauses) {
		if (!$clauses || !$this->link) return false;
	
		$query = sprintf("DELETE FROM `%s`", $table);
		if ($clauses != '*') $query .= $this->build_clauses($clauses);
	
		$this->run_query($query);
		return $this->affected_rows();
	}
	
	/*
	 *	$clauses may be of the following form: (replace the word "id" with numeric id values)
	 *		- array(0 => id, 1 => id, 3 => id, etc.)		This means we have an array of IDs to match in the WHERE clause
	 *		- "id, id, id, id"								This means we have a comma seperated list of IDs to match in the WHERE clause
	 *		- id											A single numeric value: the ID to work to match in the WHERE clause
	 *		- array(key => value, key => value, etc)		An associative array with the KEY as the column name and VALUE as the value to match on that column.
	 *		- STRING										The first character of the string must be a '!' for the string to be taken literally
	 */
	public function build_clauses($clauses, $noWhere = false, $logicalConnector = false, $table = "") {
		if ($clauses === false) return;
		$query = !$noWhere ? " WHERE " : " ";
		if (!$logicalConnector) $logicalConnector = "AND";
		if ($table and strpos($table, '.') === false) $table = "`" . $table . "`.";
		// Only a number? This is the ID we're looking for
		if (is_numeric($clauses)) {
			$query .= sprintf(" %sid = %d", $table, $clauses);
			// An exclamation mark in front of CLAUSES defines a literal WHERE clause string. Don't interpret.
		} elseif (is_string($clauses) && $clauses[0] == '!') {
			$query .= sprintf(" %s", substr($clauses, 1));
			// Are we dealing with a list of integers? Then we want to all the items whos IDs correspond with these integers
		} elseif ((is_string($clauses) && ($clauses2 = $clauses)) || (array_key_exists(0, $clauses) && is_numeric($clauses[0]) && is_vector($clauses) && ($clauses2 = array_implode_map(",", array_values($clauses), array($this, 'quote'))))) {
			$query .= sprintf(" %sid IN (%s)", $table, $clauses2);
			// If we have an ARRAY as CLAUSES we want to go through it.
		} elseif (is_array($clauses) && !empty($clauses)) {
			$connectors = array('AND', 'OR');
			$first = true;
			foreach ($clauses as $key => $value) {
				if ($first) $first = false;
				else $query .= sprintf(" %s", $logicalConnector);
				
				if (is_numeric($key) && is_string($value) && $value[0] == '!') {
					$query .= sprintf(" %s", substr($value, 1));
				} elseif (is_numeric($key) && is_array($value)) {
					$query .= " " . $this->build_clauses($value, true, false, $table);
					// If the KEY is a connector (i.e. OR or AND) we want to make a corresponding list of the VALUE
				} elseif (in_array($key, $connectors)) {
					$query .= sprintf(" (%s)", $this->build_clauses($value, true, $key, $table));
					// If the KEY is "~" then we are dealing with a SEARCH query. The VALUE should be an array
					// containing an array called 'search', with the search terms, and array called 'searchFields',
					// defining what fields are searched in and an optional value 'connector'. Use the latter to
					// change the search to an AND instead of an OR list.
				} elseif ($key == "~") {
					if (!($termsConnector = request($value['termsConnector']))) $termsConnector = 'AND';
					if (!($fieldsConnector = request($value['fieldsConnector']))) $fieldsConnector = 'OR';
					$query .= " (";
					$first2 = true;
					foreach ($value['searchFields'] as $column) {
						if ($first2) $first2 = false;
						else $query .= sprintf(" %s ", $fieldsConnector);
						$query .= " (";
						$first3 = true;
						foreach ($value['search'] as $search) {
							if ($first3) $first3 = false;
							else $query .= sprintf(" %s ", $termsConnector);
							$column = strpos($column, '.') > -1 ? $column : sprintf('%s`%s`', $table, $column);
							$query .= sprintf("%s LIKE '%%%s%%'", $column, $this->escape_clause($search));
						}
						$query .= ")";
					}
					$query .= ")";
					// None of the 2 above? Just print a simple WHERE CLAUSE statement.
					// An exclamation mark in front of CLAUSES defines a literal WHERE clause string. Don't interpret.
				} elseif (is_string($clauses) && $clauses[0] == '!') {
					$query .= sprintf(" %s", substr($clauses, 1));
				} elseif (is_vector($value) && ($value2 = array_implode_map(",", $value, array($this, 'quote')))) {
					$query .= sprintf(" %s`%s` IN (%s)", $table, $key, $value2);
				} else {
					$operator = strstr($value, '%') ? 'LIKE' : '=';
					$query .= sprintf(" %s`%s` %s %s", $table, $key, $operator, $this->quote($value));
				}
			}
		} else return false;
		return $query;
	}
	
	public function escape_clause($string) {
		return mysql_real_escape_string($string, $this->link);
	}
	
	public function quote($string) {
		//if (ctype_digit($string)) settype($string, 'int');
		if (is_bool($string)) $string = $string ? 1 : 0;
		elseif ($string === NULL) $string = 'NULL';
		elseif (is_string($string) and $this->link) $string = quote(mysql_real_escape_string($string, $this->link));
		return $string;
	}
	
	/*
	 *	Queries the database defined by the @connection variable and returns the result
	 *	if possible, or 'dies' with an error message if something went wrong.
	 *
	 *	@param query		(String)		Contains the query to be executed.
	 *
	 *	@return result		()				The result from the database query.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function run_query($query) {
		if (!$this->link) {
			echo "No connection!!";
			return false;
		}
		$explain = strpos($query, 'EXPLAIN') === false;
		if ($explain) $this->queryList[] = $query;
		if ($this->model->debug() and $explain) $time = microtime_float();
		$result = @mysql_query($query, $this->link);
		if ($this->model->debug() and $explain) {
			$this->errnoList[] = ($this->errorList[] = $result ? '' : mysql_error($this->link)) ? mysql_errno($this->link) : '';
			$this->runningTimeList[] = microtime_float() - $time;
		}
		return $result;
	}
	
	/*
	 *	Calls the appropriate fetch_assoc (or fetch_array) of the currently used database and returns
	 *	an associative array or FALSE for no result.
	 *
	 *	@param result				Contains the result given by a query on the database.
	 *	@param boolean array		Whether to return fetch_array instead of fetch_assoc
	 *
	 *	@return mixed result		An associative array or FALSE for no result.
	 *
	 *	@Author 					Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function fetch($result, $array = false) {
		return $array ? mysql_fetch_array($result) : mysql_fetch_assoc($result);
	}
	
	/*
	 *	Calls the appropriate num_rows of the currently used database and returns
	 *	the number of rows in the given result.
	 *
	 *	@param result		()				Contains the result given by a query on the database.
	 *
	 *	@return result		(Integer)		The number of rows in the database result.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function num_rows($result) {
		try {
			return mysql_num_rows($result);
		} catch (Exception $e) {
			var_dump(mysql_error($this->link));
		}
	}

	/*
	 *	Returns the latest database query, or, when all requested, returns all
	 *
	 *	@return mixed	The latest database query.
	 *
	 *	@Author			Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function getQuery($all = false) {
		return $all ? $this->queryList : array_top($this->queryList);
	}
	
	/*
	 *	Returns the latest database error, or, when all requested, returns all
	 *
	 *	@return Error		(String)		The latest database error message.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function getError($all = false) {
		return $all ? $this->errorList : array_top($this->errorList);
	}
	
	/*
	 *	Returns the latest query running time, or, when all requested, returns all
	 *
	 *	@return RunningTime		(String)		The latest database query running time.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function getRunningTime($all = false) {
		return $all ? $this->runningTimeList : array_top($this->runningTimeList);
	}
	
	/*
	 *	Returns the latest database error code (a number).
	 *
	 *	@return Error code.	(integer)		The latest database error code.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function getErrno($all = false) {
		return $all ? $this->errnoList : array_top($this->errnoList);
	}
	
	/*
	 *	Calls the appropriate affected_rows method of the currently used database and returns
	 *	the number of affected rows by the last query that was executed.
	 *
	 *	@param result		()				Contains the result given by a query on the database.
	 *
	 *	@return result		()				Returns the number of affected rows, caused by executing the query.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function affected_rows() {
		return mysql_affected_rows($this->link);
	}
	
	/*
	 *	Calls the appropriate function that returns the last generated ID with an INSERT action.
	 *
	 *	@param link			(connection)	The connection to use.
	 *
	 *	@return result		(Integer)		The last generated ID.
	 *
	 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
	 */
	public function insert_id() {
		return mysql_insert_id($this->link);
	}
	
	/**
	 * Returns a list of all queries and errors
	 * 
	 * @return array list of all queries and errors
	 */
	public function getInfo(){
		return 
			array(
				array_transpose(array('query' => $this->getQuery(true), 'error' => $this->getError(true), 'errno' => $this->getErrno(true), 'time' => $this->getRunningTime(true))),
				$this->runSelect
			);
	}
}
