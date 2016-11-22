<?php
class Advertisement {

	private function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function setLocationID($location_id) {
		$this->location_id = $location_id;
	}

	public function display() {
		$ads = $GLOBALS['model']->db()->query(
			'ads_deployments',
			array('location_id' => $this->location_id, '!TO_DAYS(NOW()) >= TO_DAYS(start_date) AND TO_DAYS(NOW()) <= TO_DAYS(end_date)'),
			array(
				'orderBy' => 'rand()',
				'join' => array('table' => 'ads_media', 'fields' => '*', 'on' => array('media_id', 'media_id')),
				'transpose' => array('selectKey' => 'deployment_id', 'selectValue' => true)
			)
		);
		
		// If no ads are found, return empty string
		if (!$ads) return '';
		
		// Check if shown ads are already logged in the session
		$shownAds = request($_SESSION['shownAds'][$this->location_id]);
		if (!is_array($shownAds)) $shownAds = array();
		
		// Remove shown ads. If the list is empty, reset and restart
		if (!$ads2 = array_remove_keys($ads, $shownAds)) {
			$ads2 = $ads;
			$shownAds = array();
		}
		
		// Get ad
		$ad = array_shift($ads2);
		// Add to shown ads
		$shownAds[] = $ad['deployment_id'];
		$_SESSION['shownAds'][$this->location_id] = $shownAds;
		
		$this->setData($ad);
		return $this->getHTML();
	}

	private function getHTML() {
		$content = '';
		switch($this->type) {
			case 'jpg':
			case 'png':
			case 'gif':
				$content = "<img src=\"/images/ads/" . $this->media_id . '.' . $this->type . "\" width=\"$this->width\" height=\"$this->height\" alt=\"*\" />";
				if ($this->ad_text) $content = sprintf("<a href=\"%s\">%s</a>", $this->ad_text, $content);
			break;
			case 'swf':
				$content = sprintf('<object type="application/x-shockwave-flash" data="/images/ads/%d.swf" width="%d" height="%d">
						<param name="wmode" value="transparent">
						<!--[if IE]><param name="movie" value="/images/ads/%d.swf"><![endif]--> 
					</object>', $this->media_id, $this->width, $this->height, $this->media_id);
				if ($this->ad_text) $content = sprintf("<a href=\"%s\">%s</a>", $this->ad_text, $content);
			break;

			case 'text':
				$content = $this->ad_text;
			break;
		}

		return $content;
	}
}
?>