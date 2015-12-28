<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Touzet David <dtouzet@gmail.com>
*  All rights reserved
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * <Class description>
 *
 * @author Touzet David <dtouzet@gmail.com>
 */


/** 
 * 
 *
 */
class db {
	public $link;
	
	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function connect($server, $username, $password, $db) {
		if (!extension_loaded('mysql')) {
			$message = 'Database Error: It seems that MySQL support for PHP is not installed!';
			throw new Exception($message, 1);
		}
		
		$this->link = mysql_connect($server, $username, $password);
		if (!$this->link){
			$message = 'Impossible de se connecter : ' . mysql_error();
			throw new Exception($message, 2);
		}
		
		if (!mysql_select_db($db)) {
			$message = 'Impossible de sélectionner la base de données : ' . mysql_error();
			throw new Exception($message, 3);
		}
	}
	
	public function query($query){
		$res = mysql_query($query, $this->link);
		return $res;
	}
	
	public function select_query($select = '', $from = '' , $where = '', $orderBy = '', $limit = '', $groupBy = '') {
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table .
			(strlen($where_clause) > 0 ? ' WHERE ' . $where_clause : '');

			// Group by:
		$query .= (strlen($groupBy) > 0 ? ' GROUP BY ' . $groupBy : '');

			// Order by:
		$query .= (strlen($orderBy) > 0 ? ' ORDER BY ' . $orderBy : '');

			// Group by:
		$query .= (strlen($limit) > 0 ? ' LIMIT ' . $limit : '');
		
		return $query;
	}
	
	public function selectQuery($select = '', $from = '' , $where = '', $orderBy = '', $limit = '', $groupBy = ''){
		$query = $this->select_query($select, $from, $where, $orderBy, $limit, $groupBy);
		return $this->query;
	}
	
	public function fetch_selectQuery($select = '', $from = '' , $where = '', $orderBy = '', $limit = '', $groupBy = ''){
		$res = $this->selectQuery($select, $from, $where, $orderBy, $limit, $groupBy);
		$output = array();

		while ($output[] = $this->mysql_fetch_assoc($res));
		array_pop($output);
		
		$this->mysql_free_result($res);
		return $output;
	}
	
	
	/**
	 * Escaping and quoting values for SQL statements.
	 */
	public function fullQuoteStr($str) {
		return '\'' . mysql_real_escape_string($str, $this->link) . '\'';
	}

	/**
	 * Will fullquote all values in the one-dimensional array so they are ready to "implode" for an sql query.
	 */
	public function fullQuoteArray($arr, $noQuote = FALSE) {
		if (is_string($noQuote)) {
			$noQuote = explode(',', $noQuote);
			// sanity check
		} elseif (!is_array($noQuote)) {
			$noQuote = FALSE;
		}

		foreach($arr as $k => $v) {
			if ($noQuote === FALSE || !in_array($k, $noQuote)) {
				$arr[$k] = $this->fullQuoteStr($v);
			}
		}
		return $arr;
	}
	
	
	/**
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Usage count/core: 4
	 *
	 * @param	string		See exec_INSERTquery()
	 * @param	array		See exec_INSERTquery()
	 * @param	string/array		See fullQuoteArray()
	 * @return	string		Full SQL query for INSERT (unless $fields_values does not contain any elements in which case it will be false)
	 */
	public function INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {

			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (is_array($fields_values) && count($fields_values)) {

				// quote and escape values
			$fields_values = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);

				// Build query:
			$query = 'INSERT INTO ' . $table .
				' (' . implode(',', array_keys($fields_values)) . ') VALUES ' .
				'(' . implode(',', $fields_values) . ')';


			return $query;
		}
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	string		Full SQL query for INSERT (unless $rows does not contain any elements in which case it will be false)
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (count($rows)) {
				// Build query:
			$query = 'INSERT INTO ' . $table .
				' (' . implode(', ', $fields) . ') VALUES ';

			$rowSQL = array();
			foreach ($rows as $row) {
					// quote and escape values
				$row = $this->fullQuoteArray($row, $table, $no_quote_fields);
				$rowSQL[] = '(' . implode(', ', $row) . ')';
			}

			$query .= implode(', ', $rowSQL);

				// Return query:
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}

			return $query;
		}
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Usage count/core: 6
	 *
	 * @param	string		See exec_UPDATEquery()
	 * @param	string		See exec_UPDATEquery()
	 * @param	array		See exec_UPDATEquery()
	 * @param	array		See fullQuoteArray()
	 * @return	string		Full SQL query for UPDATE
	 */
	public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			$fields = array();
			if (is_array($fields_values) && count($fields_values)) {

					// quote and escape values
				$nArr = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);

				foreach ($nArr as $k => $v) {
					$fields[] = $k.'='.$v;
				}
			}

				// Build query:
			$query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) .
				(strlen($where) > 0 ? ' WHERE ' . $where : '');

			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new InvalidArgumentException(
				'TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !',
				1270853880
			);
		}
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 3
	 *
	 * @param	string		See exec_DELETEquery()
	 * @param	string		See exec_DELETEquery()
	 * @return	string		Full SQL query for DELETE
	 */
	public function DELETEquery($table, $where) {
		if (is_string($where)) {

				// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM ' . $table .
				(strlen($where) > 0 ? ' WHERE ' . $where : '');

			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new InvalidArgumentException(
				'TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !',
				1270853881
			);
		}
	}


	public function mysql_error() {
		return mysql_error($this->link);
	}

	
	public function mysql_errno() {
		return mysql_errno($this->link);
	}


	public function mysql_num_rows($res) {
		return mysql_num_rows($res);
	}

	public function mysql_fetch_assoc($res) {
		return mysql_fetch_assoc($res);
	}

	public function mysql_fetch_row($res) {
		return mysql_fetch_row($res);
	}

	public function mysql_free_result($res) {
		return mysql_free_result($res);
	}

	public function mysql_insert_id() {
		return mysql_insert_id($this->link);
	}

	public function mysql_affected_rows() {
		return mysql_affected_rows($this->link);
	}


	public function mysql_data_seek($res, $seek) {
		return mysql_data_seek($res, $seek);
	}

	public function mysql_field_type($res, $pointer) {
		return mysql_field_type($res, $pointer);
	}

}
?>