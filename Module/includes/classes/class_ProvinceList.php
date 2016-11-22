<?php
class ProvinceList {
	
	public function getArray() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM provinces
							ORDER BY province');
		while ($row = $rs->getRow())
			$province[$row['province_id']] = $row['province'];
		return $province;
	}
}
?>