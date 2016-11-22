<?php
class AdMedia {

	public function __construct($media_id = false) {
		$this->load($media_id);
	}
	
	public function load($media_id = false) {
		if ($media_id > 0) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM ads_media
								WHERE media_id = '.$media_id);
			$this->setData($rs->getRow());
		}
	}
	
	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function setFiles($files) {
		$this->files = $files;
	}

	public function getForm() {
		$listing = new ListingsItem($this->listing_id);
		
		$content = FormHelper::open('form_media_proc.php', array('file_upload' => true));
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('media_id', $this->media_id);
		if ($depl_id = request($_GET['deployment_id'])) $content .= FormHelper::hidden('deployment_id', $depl_id);
		$f[] = FormHelper::select('Advertiser', 'advertiser_id', AdvertiserList::getArray(), $this->advertiser_id);
		$f[] = FormHelper::file('File', 'file');
		$f[] = FormHelper::input('Website', 'website', $this->website ? $this->website : $this->ad_text, array('type' => 'url'));
		$f[] = $listing->getFormInput();
		$content .= FormHelper::fieldset('Advertisement', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}

	public function displayMedium() {
		switch ($this->type) {
			case 'gif':
			case 'jpg':
			case 'png':
				return "<img src=\"".AD_STORE_URL.$this->media_id.'.'.$this->type."\" width=\"$this->width\" height=\"$this->height\">";
			break;

			case 'text':
				return $this->ad_text;
			break;
		}
	}

	public function getDeployForm($deployment_id = '') {
		global $model;
		$content = '';
		if (ctype_digit($deployment_id)) {
			$data = $model->db()->query('ads_deployments', array('deployment_id' => $deployment_id), array('singleResult' => true));
			if ($data) $this->load($data['media_id']);
		}
		
		$ad_media = new AdMedia($this->media_id);
		$content .= $ad_media->displayMedium();
		if ($deployment_id) {
			$content .= sprintf('<br><a href="form_media.php?advertiser_id=%d&deployment_id=%d">change...</a>', $this->advertiser_id, $data['deployment_id']);
		}
		$content .= '<br /><br />';
		
		
		$listing = new ListingsItem($data['listing_id']);
		
		$content .= FormHelper::open('form_deploy_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('media_id', $this->media_id);
		$content .= FormHelper::hidden('deployment_id', $data['deployment_id']);
		$f[] = FormHelper::select('Location', 'location_id', AdLocationList::getArray(), $data['location_id']);
		$f[] = FormHelper::input('Start Date', 'start_date', $data['start_date'] ? $data['start_date'] : date('Y-m-d'), array('type' => 'date'));
		$f[] = FormHelper::input('End Date', 'end_date', $data['end_date'] ? $data['end_date'] : date('Y-m-d'), array('type' => 'date'));
		$f[] = FormHelper::input('Fee', 'fee', $data['fee'], array('type' => 'number'));
		$f[] = FormHelper::input('Website', 'website', $data['website'], array('type' => 'url'));
		$f[] = $listing->getFormInput();
		$content .=  FormHelper::fieldset('Deployment', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}

	public function setAdvertiserID($advertiser_id) {
		$this->advertiser_id = $advertiser_id;
	}

	public function getAdvertiserID() {
		return $this->advertiser_id;
	}

	public function save() {
		global $model;
		$uploader = $model->tool('uploader');
		
		$data = array(
			'advertiser_id' => $this->advertiser_id,
			'listing_id' => $this->listing_id,
			'website' => $this->website
		);
		
		if ($uploader->exists('file')) {
			$o = array();
			$uploader->setUploadFolder(AD_STORE_FILEPATH);
			if ($uploader->captureUpload('file')) {
				$file = $uploader->successful[0]['target'];
				$info = getimagesize($file);
				$ext = $uploader->successful[0]['extension'];
				if ($ext = 'jpeg') $ext = 'jpg';
				$data['type'] = $ext;
				$data['width'] = $info[0];
				$data['height'] = $info[1];
			} else return false;
		}
		
		if ($this->media_id and is_numeric($this->media_id)) {
			$result = -1 < $model->db()->update('ads_media', array('media_id' => $this->media_id), $data);
		} else {
			$this->media_id = $model->db()->insert('ads_media', $data);
			$result = $this->media_id > 0;
		}
		
		if ($result) {
			if ($file) {
				$target = AD_STORE_FILEPATH . $this->media_id . '.' . $ext;
				if (rename($file, $target)) {
					if ($deployment_id = request($_POST['deployment_id']) and is_numeric($deployment_id)) {
						return -1 < $model->db()->update('ads_deployments', array('deployment_id' => $deployment_id), array('media_id' => $this->media_id));
					}
					return true;
				}
			} else return true;
		}
		return false;
	}

	public function saveDeployment() {
		global $model;
		$this->fee = !$this->fee ? 0 : (int) $this->fee;
		
		$attr = array('location_id', 'media_id', 'start_date', 'end_date', 'fee', 'website', 'listing_id');
		$args = array_select_keys($attr, $_POST);
		
		if ($this->deployment_id and is_numeric($this->deployment_id)) {
			$result = -1 < $model->db()->update('ads_deployments', array('deployment_id' => $this->deployment_id), $args);
		} else {
			$result = 0 < $model->db()->insert('ads_deployments', $args);
		}
		
		return $result;
	}
}
?>