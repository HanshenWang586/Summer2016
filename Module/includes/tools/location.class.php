<?php

/**
 * @author Yereth Jansen
 *
 * Class which handles all security (login and security checks).
 */

class LocationTools extends CMS_Class {
	public function init($args) {
		
	}
	
	public function processLanguageInfo($fields) {
		if (array_key_exists('city_id', $fields)) {
			$country = $this->getCountries(NULL, $fields['city_id'], true);
			if ($country) $fields['country'] = $country[0]['langName'];
			$fields['city'] = $this->getCityLang($fields['city_id']);
		}
		if (array_key_exists('district_id', $fields)) $fields['district'] = $this->getDistrictLang($fields['district_id']);
		
		// Add the full address
		$elements = array('address','district','city','country');
		$address = array();
		foreach($elements as $el) if (array_key_exists($el, $fields)) $address[] = $fields[$el];
		$fields['fullAddress'] = implode(', ', $address);
		
		return $fields;
	}
	
	public function getCountries($country = NULL, $city = NULL, $getFull = false) {
		$where = false;
		$options = array('orderBy' => 'iso');
		if ($country) $where = $this->getCountryClauses($country);
		elseif ($city) {
			if ($clauses = $this->getCityClauses($city)) $options['join'] = array('table' => 'cities', 'on' => array('iso', 'country'), 'where' => $clauses);
		}
		$countries = $this->db()->query('countries', $where, $options);
		$return = array();
		foreach($countries as $country) {
			$country['langName'] = $this->lang(strtoupper('COUNTRY_' . $country['iso']), false, false, true);
			if ($getFull) $return[] = $country;
			else $return[$country['iso']] = $country['langName'];
		}
		return $return;
	}
	
	private function getCountryClauses($country, $allowUnsupported = false) {
		$clauses = $allowUnsupported ? array() : array('supported' => 1);
		if (is_array($country)) $clauses['iso'] = $clauses;
		elseif (is_string($country)) {
			if (strlen($country) == 2) $clauses['iso'] = strtoupper($country);
			else $clauses['name'] = $country;
		}
		return $clauses;
	}
	
	private function getCityClauses($city, $allowUnsupported = false) {
		$clauses = $allowUnsupported ? array() : array('supported' => 1);
		if (is_numeric($city) || is_array($city)) $clauses['id'] = $city;
		elseif (is_string($city)) $clauses['name'] = $city;
		return $clauses;
	}
	
	/**
	 * Retrieves a list of all cities. If $country is set, it will try to retrieve a list of cities based on the country id,
	 * country 2 character ISO code or country name.
	 * 
	 * @param bool|int|string $country
	 * @return array In the form of 'id' => 'name'
	 */
	public function getCities($country = NULL, $getFull = false, $city = NULL) {
		$options = array('orderBy' => 'name', 'getFields' => 'cities.*');
		if ($country && $clauses = $this->getCountryClauses($country)) {
			$options['join'] = array('table' => 'countries', 'on' => array('country', 'iso'), 'where' => $clauses);
		}
		$cities = $this->db()->query('cities', $this->getCityClauses($city), $options);
		$return = array();
		foreach($cities as $city) {
			$city['langName'] = $this->lang(strtoupper('CITY_' . $city['country'] . '_' . $city['name']), false, false, true);
			if ($getFull) $return[] = $city;
			else $return[$city['id']] = $city['langName'];
		}
		return $return;
	}
	
	/**
	 * Gets the language string of the city in the currently selected language
	 * 
	 * @param int|string $city The id of the city
	 * 
	 * @return boolean|string
	 */
	public function getCityLang($city) {
		$city = $this->db()->query('cities', $this->getCityClauses($city), array('getFields' => 'cities.name, cities.country', 'singleResult' => true));
		if ($city) return $this->lang(strtoupper('CITY_' . $city['country'] . '_' . $city['name']), false, false, true);
		return false;
	}
	
	/**
	 * Gets a list of all the districts in the database. If the $city parameter is set, it will try to find the city and
	 * return the districts of the selected city. Best is to give the city id, in case there's more cities with the same name.
	 * 
	 * @param bool|string|int $city
	 * @param bool $groupByCity If true, the results will be grouped by city_id
	 * @return array In the form of 'id' => 'name'
	 */
	public function getDistricts($city = false, $groupByCity = false, $addJS = false) {
		$options = array(
			'getFields' => 'districts.*, cities.name AS city',
			'join' => array('table' => 'cities', 'on' => array('city_id', 'id'), 'where' => $this->getCityClauses($city)),
			'orderBy' => 'name',
			'callback' => array($this, 'getLangStringDistrict')
		);
		
		if ($groupByCity) $options['arrayGroupBy'] = 'city_id';
		else $options['transpose'] = array('selectKey' => 'id', 'selectValue' => 'langName');
		
		$districts = $this->db()->query('districts', false, $options);
		if ($groupByCity && $addJS) {
			$this->tool('html')->addJSVariable('districts', $districts);
			$this->tool('html')->addJS('js/templates/districts.js');
		}
		return $districts;
	}
	
	public function getLangStringDistrict(array $district) {
		if (isset($district['city'], $district['name'])) $district['langName'] = $this->lang(strtoupper('DISTRICT_' .  $district['city'] . '_' . $district['name']), false, false, true);
		unset($district['name']);
		return $district;
	}
	
	/**
	 * Gets the language string of the district in the currently selected language
	 * 
	 * @param int $district_id The id of the district
	 * 
	 * @return boolean|string
	 */
	public function getDistrictLang($district_id) {
		if (is_numeric($district_id)) {
			$district = $this->db()->query('districts', $district_id, array('getFields' => 'districts.name, cities.name AS city', 'join' => array('table' => 'cities', 'on' => array('city_id', 'id'))));
			if ($district) return $this->lang(strtoupper('DISTRICT_' .  $district['city'] . '_' . $district['name']), false, false, true);
		}
		return false;
	}
}

?>