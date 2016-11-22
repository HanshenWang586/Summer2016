<?php
class AdLocation {

	public function __construct($location_id = '') {
		if (ctype_digit($location_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT	*
								FROM ads_locations
								WHERE location_id = '.$location_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function getDescription() {
		return $this->description;
	}

	public function getCurrentAds() {
		$db = new DatabaseQuery;
		$rs = $db->execute('	SELECT *
								FROM ads_deployments
								WHERE location_id = '.$this->location_id.'
								AND NOW() >= start_date
								AND NOW() <= end_date');

		while ($row = $rs->getRow()) {
			$media = new AdMedia($row['media_id']);
			$ads[] = $media->displayMedium();
		}

		return HTMLHelper::wrapArrayInUl($ads);
	}
}
?>