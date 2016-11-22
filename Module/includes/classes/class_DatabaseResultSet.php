<?php
class DatabaseResultSet {
	private $rs;
	
	public function __construct($rs = false) {
		if ($rs) $this->rs = $rs;
	}
	
	public function setResource($rs) {
		$this->rs = $rs;
	}

	public function getNum() {
		return $this->rs->num_rows;
	}

	public function getRow() {
		return $this->rs->fetch_assoc();
	}

	public function reset() {
		$this->rs->data_seek(0);
	}

	public function dump() {
		while ($row = $this->getRow()) {
			$content .= print_r($row, true);
		}
		$this->reset();
		return $content;
	}
}
?>