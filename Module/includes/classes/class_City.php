<?php
class City {
	
	public function __construct($city_id = '') {
		if (ctype_digit((string) $city_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM listings_cities
								WHERE city_id = ' . (int) $city_id);
			$this->setData($rs->getRow());
		}
		else {
			$this->city_id = 0;
			$this->city_en = 'China';
			$this->code = 'china';
			$this->city_zh = '中国';
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function getURL() {
		global $model;
		return $model->url(array('m' => 'listings', 'view' => 'city', 'id' => $this->getCityCode()));
	}
	
	private function getProvince() {
		$province = new Province($this->province_id);
		return $province->getName();
	}

	public function getName($lang = 'en') {
		// TODO - add 市/县/etc
		$key = 'city_'.$lang;
		return $this->$key;
	}

	public function getCityCode() {
		return $this->code;
	}

	public function getLink($lang = 'en') {
		return HTMLHelper::link($this->getURL(), $this->getName($lang));
	}

	public function displayAdminRow() {
		$city_en = $this->city_en;
		if ($this->live == 1)
			$city_en = '<b>'.$city_en.'</b>';

		return "<tr>
		<td>$this->city_id</td>
		<td>$this->code</td>
		<td>$city_en</td>
		<td nowrap class=\"chinese\">$this->city_zh</td>
		<td>".$this->getProvince()."</td>
		<td>$this->phone_code</td>
		<td>$this->yahoo</td>
		<td>$this->c_latitude</td>
		<td>$this->c_longitude</td>
		<td>$this->lat_correction</td>
		<td>$this->lon_correction</td>
		<td>$this->live</td>
		<td><a href=\"form_place.php?city_id=$this->city_id\">Edit</a></td>
		</tr>";
	}

	public function getForm() {
		$this->c_latitude = intval($this->c_latitude)==0 ? '' : $this->c_latitude;
		$this->c_longitude = intval($this->c_longitude)==0 ? '' : $this->c_longitude;

		if ($this->city_id == 0) {
			$this->city_id = '';
			$this->city_en = '';
			$this->city_zh = '';
		}
		
		$content .= FormHelper::open('form_place_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('city_id', $this->city_id);
		$f[] = FormHelper::select('Province', 'province_id', ProvinceList::getArray(), $this->province_id);
		$f[] = FormHelper::input('Code', 'code', $this->code);
		$f[] = FormHelper::input('City (E)', 'city_en', $this->city_en);
		$f[] = FormHelper::input('City (C)', 'city_zh', $this->city_zh, array('class' => 'chinese'));
		$f[] = FormHelper::input('Phone code', 'phone_code', $this->phone_code);
		$f[] = FormHelper::input('Yahoo', 'yahoo', $this->yahoo);
		$f[] = FormHelper::input('Latitude', 'c_latitude', $this->c_latitude);
		$f[] = FormHelper::input('Longitude', 'c_longitude', $this->c_longitude);
		$f[] = FormHelper::radio('Live', 'live', array(0 => 'No', 1 => 'Yes'), $this->live);
		$content .= FormHelper::fieldset('City', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}

	public function save() {
		$this->c_latitude = $this->c_latitude == '' ? 0 : $this->c_latitude;
		$this->c_longitude = $this->c_longitude == '' ? 0 : $this->c_longitude;
		$this->lat_correction = $this->lat_correction == '' ? 0 : $this->lat_correction;
		$this->lon_correction = $this->lon_correction == '' ? 0 : $this->lon_correction;
		$this->live = $this->live == '' ? 0 : 1;

		$db = new DatabaseQuery;

		if (!ctype_digit($this->city_id)) {
			$db->execute("	INSERT INTO listings_cities (	code,
															province_id,
															city_en,
															city_zh,
															phone_code,
															yahoo,
															c_latitude,
															c_longitude,
															lat_correction,
															lon_correction,
															live
														)
							VALUES (	'$this->code',
										$this->province_id,
										'$this->city_en',
										'$this->city_zh',
										'$this->phone_code',
										'$this->yahoo',
										'$this->c_latitude',
										'$this->c_longitude',
										'$this->lat_correction',
										'$this->lon_correction',
										$this->live
									)");
		}
		else {
			$db->execute("	UPDATE listings_cities
							SET	code = '$this->code',
								province_id = $this->province_id,
								city_en = '$this->city_en',
								city_zh = '$this->city_zh',
								phone_code = '$this->phone_code',
								yahoo = '$this->yahoo',
								c_latitude = '$this->c_latitude',
								c_longitude = '$this->c_longitude',
								lat_correction = '$this->lat_correction',
								lon_correction = '$this->lon_correction',
								live = $this->live
							WHERE city_id = $this->city_id");
		}
	}

	/**
	 * @static
	 */
	public static function getCityIDFromName($city) {
		if ($city != 'china') {
			$result = $GLOBALS['model']->db()->query('listings_cities', array('code' => $city), array('selectField' => 'city_id'));
			return $result;
		} else
			HTTP::throw404();
	}

	public function getCategoryIDs() {
		$category_ids = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT category_id
							FROM listings_sitecitycat
							WHERE city_id = '.$this->city_id);
		while($row = $rs->getRow())
			$category_ids[] = $row['category_id'];
		return $category_ids;
	}

	public function getListingsTally() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT COUNT(*) AS tally
							FROM listings_data d
							WHERE city_id = '.$this->city_id.'
							AND d.status = 1');
		$row = $rs->getRow();
		return $row['tally'];
	}

	public function getLatestReviewIDs() {
		$review_ids = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT review_id
							FROM listings_reviews r
							LEFT JOIN listings_data d ON (r.listing_id = d.listing_id)
							WHERE city_id = '.$this->city_id.'
							AND d.status = 1
							ORDER BY ts DESC
							LIMIT 10');
		while($row = $rs->getRow())
			$review_ids[] = $row['review_id'];
		return $review_ids;
	}

	function getCityID() {
		return $this->city_id;
	}

	function getLatitude() {
		return $this->c_latitude;
	}

	function getLongitude() {
		return $this->c_longitude;
	}

	function getLatCorrection()
	{
	return $this->lat_correction;
	}

	function getLonCorrection()
	{
	return $this->lon_correction;
	}

	public function getMapHTML($cat_id = 0) {
		global $model;
		if ($this->getLatitude() != 0) {
			return sprintf("
							<div class=\"google_map\" id=\"google_maps_city\" data-maps-plugin=\"markerClusterer\" data-search-form=\"searchListingsForm\" data-city-id=\"%d\" data-latitude=\"%s\" data-longitude=\"%s\" data-category-id=\"%s\">
								<div class=\"infoHeader\">
									<span class=\"icon icon-info\"> </span>
									<span class=\"infoText\">
										<span class=\"default\">%s</span>
										<span class=\"listing\"></span>
									</span>
								</div>
								<div class=\"mapContainer\"></div>
							</div>
						",
				$this->city_id,
				$this->getLatitude(),
				$this->getLongitude(),
				$cat_id,
				$model->module('lang')->get('ListingsModel', 'MAP_INFO_HEADER')
			);
		}
	}

	public function getWeatherCode() {
		return $this->yahoo;
	}

	public function getPageTitle() {
		return $this->city_en.' '.$this->city_zh;
	}
	
	public function getProvinceID() {
		return $this->province_id;
	}
}
?>
