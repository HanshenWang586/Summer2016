<?php
class AdLocationList {

	/**
	 * @static
	 */
	public static function getArray() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM ads_locations
							ORDER BY description');
		while ($row = $rs->getRow())
			$locations[$row['location_id']] = $row['description'];
		return $locations;
	}
}
?>
