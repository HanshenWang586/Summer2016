<?php
class AdvertiserList {
	
	private function getData() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT DISTINCT advertiser, url
						   FROM ads_advertisers a
						   LEFT JOIN ads_media m ON (m.advertiser_id = a.advertiser_id)
						   LEFT JOIN ads_deployments d ON (m.media_id = d.media_id)
						   WHERE TO_DAYS(NOW()) >= TO_DAYS(start_date)
						   AND TO_DAYS(NOW()) <= TO_DAYS(end_date)
						   ORDER BY end_date DESC');
		return $rs;
	}

	public function getFeatAdv() {
		$rs = $this->getData();

		while ($row = $rs->getRow()) {
			if ($row['url'] != '')
				$items[] = "<a href=\"{$row['url']}\"".(strpos($row['url'], 'http://') === 0 ? " rel=\"nofollow\" target=\"_blank\"" : '').">{$row['advertiser']}</a>";
			else
				$items[] = $row['advertiser'];
		}

		return HTMLHelper::wrapArrayInUl($items);
	}
	
	public function geteNewsFeaturedAdvertisers($site, $color) {
		global $model;
		$rs = $this->getData();
		
		while ($row = $rs->getRow()) {
			if ($row['url'] != '')
				$items[] = "<a href=\"".(strpos($row['url'], 'http://') === 0 ? $row['url'] : $model->module('preferences')->get('url') . $row['url'])."\" target=\"_blank\" style=\"text-decoration:none;color:$color;\">{$row['advertiser']}</a>";
			else
				$items[] = $row['advertiser'];
		}
		
		return implode(', ', $items);
	}
	
	/**
	 * @static
	 * @return array
	 */
	public static function getArray() {
		return $GLOBALS['model']->db()->query('ads_advertisers', false, array('orderBy' => 'advertiser', 'transpose' => array('advertiser_id', 'advertiser')));
	}
}
?>