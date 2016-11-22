<?php
class CityList {

	public function getAdmin() {
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT 	*,
										IF(c_latitude != 0, c_latitude, '') AS c_latitude,
										IF(c_longitude != 0, c_longitude, '') AS c_longitude
								FROM listings_cities ORDER BY city_en");
		$content .= "<table class=\"gen_table\" cellspacing=\"1\">
		<tr>
		<td><b>ID</b></td>
		<td><b>Code</b></td>
		<td><b>City</b></td>
		<td class=\"chinese\"><b>城市</b></td>
		<td><b>Province</b></td>
		<td><b>Phone code</b></td>
		<td><b>Yahoo!</b></td>
		<td><b>Latitude</b></td>
		<td><b>Longitude</b></td>
		<td><b>Latitude Correction</b></td>
		<td><b>Longitude Correction</b></td>
		<td><b>Live</b></td>
		<td></td>
		</tr>";
		
		while ($row = $rs->getRow()) {
			$city = new City;
			$city->setData($row);
			$content .= $city->displayAdminRow();
		}
		
		$content .= '</table>';
		return $content;
	}
	
	public static function getArray() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT city_id, city_en
							FROM listings_cities
							WHERE live = 1
							ORDER BY city_en');
		while ($row = $rs->getRow())
			$cities[$row['city_id']] = $row['city_en'];
		return $cities;
	}
	
	public function getSelectBox() {
		global $model, $site;
		$cities = $model->db()->query('listings_cities', array('city_id' => $site->getCityIDs()), array('transpose' => array('selectKey' => 'city_id', 'selectValue' => 'city_en')));
		return FormHelper::select('Select City', 'city_id', $cities, '');
	}
	
	public static function getPickList($with_tally = false) {
		global $user, $site, $model;
		$tally = 0;
		
		if (true) {
			$args = array(
				'join' => array('table' => 'listings_data', 'where' => array('status' => 1), 'on' => array('city_id', 'city_id'), 'fields' => 'count(listing_id) AS tally'),
				'groupBy' => 'listings_data.city_id',
				'orderBy' => 'tally',
				'order' => 'DESC',
			);
		} else {
			$args = array('orderBy' => 'city_en');
		}
		$args['getFields'] = array('code', 'city_en');
		$args['callback'] = array(CityList, 'createCityLink');
		$cities = $model->db()->query('listings_cities', array('city_id' => $site->getCityIDs()), $args);
		return HTMLHelper::wrapArrayInUl($cities);
	}
	
	public static function createCityLink($data) {
		if ($data['tally']) $data['tally'] = ' <span class="tally">(' . $data['tally'] . ')</span>';
		return sprintf('<a href="/en/listings/city/%s/">%s %s</a>', $data['code'], $data['city_en'], $data['tally']);
	}
	
	public static function getByProvinceID($province_id) {
		$cities = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT city_id
							FROM listings_cities
							WHERE province_id = '.$province_id.'
							ORDER BY city_en');
		while ($row = $rs->getRow())
			$cities[] = $row['city_id'];
		return $cities;
	}
}
?>
