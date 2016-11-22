<?php

// A class that handles many to many relationships
class M2mTools extends CMS_Class {
	private $m2m, $tableLeft, $fieldLeft, $tableRight, $fieldRight;
	
	public function init($args) {
		
	}

	public function setTables($m2mTable, $fieldLeft, $fieldRight, $tableLeft = false, $tableRight = false) {
		// The many-to-many table in question.
		$this->m2m			= $m2mTable;

		// The field names that correspond with the ID pairs.
		$this->fieldLeft	= $fieldLeft;
		$this->fieldRight	= $fieldRight;

		// The tables belonging to the field IDs. Assumed is that the field names correspond with `table1.id` value, for example.
		$this->tableLeft 	= $tableLeft;
		$this->tableRight 	= $tableRight;
	}

	// Removes membership for a many-to-many relationship-table in all possible combinations the 2 lists (arrays) can make..
	public function deleteMembership($idsLeft = false, $idsRight = false) {
		$clauses = array();
		if ($idsLeft) $clauses[$this->fieldLeft] = $idsLeft;
		if ($idsRight) $clauses[$this->fieldRight] = $idsRight;
		return $this->db()->delete($this->m2m, $clauses);
	}

	// Adds membership for a many-to-many relationship-table in all possible combinations the 2 lists (arrays) can make..
	function addMembership($idsLeft, $idsRight, array $extraFields = NULL) {
		if (!$idsLeft || !$idsRight) return false;
		if (!is_array($idsLeft)) {
			$idsLeft = explode(',', $idsLeft);
		}
		if (!is_array($idsRight)) {
			$idsRight = explode(',', $idsRight);
		}
		$query = sprintf("INSERT IGNORE INTO %s (%s, %s) VALUES ", $this->m2m, $this->fieldLeft, $this->fieldRight);
		if ($idsLeft && $idsRight) {
			foreach ($idsLeft as $idLeft) {
				foreach ($idsRight as $idRight) {
					if (is_numeric($idLeft) && is_numeric($idRight)) {
						$query .= sprintf("(%d, %d),", $idLeft, $idRight);
					}
				}
			}
			$query = substr($query, 0, strlen($query) - 1); // Remove the last comma, which should not be there of course.
			$link = connect();
			db_query($query, $link);
			$affected = db_affected_rows($link);
			disconnect($link);
			return $affected;
		}
		return false;
	}

	public function readMembership($ids, $idsField, $returnField) {
		if (!$ids || !is_string($idsField) || !is_string($returnField)) {
			echo "ERROR";
			return false;
		}
		$clause = $this->_createClause($ids, $idsField);
		$query = sprintf("SELECT %s AS id FROM %s WHERE %s", $returnField, $this->m2m, $clause);
		$link = connect();
		$result = db_query($query, $link);
		$returnArray = array();
		while ($row = db_fetch_assoc($result)) {
			$returnArray[] = $row['id'];
		}
		disconnect($link);
		return $returnArray;
	}

	public function getCorrespondingEntriesQuery($idsLeft, $idsRight, $table, $correspondingIDField) {
		$clauses = array();
		if ($clause = $this->_createClause($idsLeft, $this->fieldLeft, $this->m2m))
		$clauses[] = $clause;
		if ($clause = $this->_createClause($idsRight, $this->fieldRight, $this->m2m))
		$clauses[] = $clause;

		$query = "SELECT $table.* FROM $table, $this->m2m WHERE $table.id = $this->m2m.$correspondingIDField";

		foreach ($clauses as $clause) {
			$query .= " AND " . $clause;
		}

		return $query;
	}

	private function _createClause($ids, $field, $table = false) {
		if ($ids) {
			if (is_array($ids)) {
				$ids = implode(',', $ids);
			}
			$clause = sprintf("%s IN (%s)", $field, $ids);
			if ($table) {
				$clause = sprintf("%s.%s", $table, $clause);
			}
			return $clause;
		}
		return false;
	}
}

?>