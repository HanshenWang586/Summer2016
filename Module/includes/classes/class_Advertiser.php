<?php
class Advertiser {

	public function __construct($advertiser_id = '') {
		if (ctype_digit($advertiser_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM ads_advertisers
								WHERE advertiser_id = '.$advertiser_id);
			$this->setData($rs->getRow());
		}
	}

	public function getAdvertiserID() {
		return $this->advertiser_id;
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function getForm() {
		$content = FormHelper::open('form_advertiser_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('advertiser_id', $this->advertiser_id);
		$f[] = FormHelper::input('Name', 'advertiser', $this->advertiser);
		$f[] = FormHelper::input('URL', 'url', $this->url, array('guidetext' => 'Please include the http:// part'));
		$f[] = FormHelper::input('Listing_id', 'listing_id', $this->listing_id, array('type' => 'number'));
		$content .= FormHelper::fieldset('Advertiser', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}

	public function save() {
		global $model;
		$db = $model->db();
		$values = array(
					'advertiser' => $this->advertiser,
					'url' => $this->url
				);
		if ($this->listing_id) $values['listing_id'] = $this->listing_id;
		if (ctype_digit($this->advertiser_id)) {
			$db->update('ads_advertisers', array('advertiser_id' => $this->advertiser_id), $values);
		} else {
			$this->advertiser_id = $db->insert('ads_advertisers', $values);
		}
	}
}
?>