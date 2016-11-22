<?php
class ListingsPhoto {

	public function __construct($photo_id = '') {
		if (ctype_digit($photo_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM listings_photos
								WHERE photo_id = '.$photo_id);
			$this->setData($rs->getRow());
		}
	}

	public function getListingID() {
		return $this->listing_id;
	}
	
	public function setData($row) {
		if (is_array($row)) {
			foreach($row as $key => $value)
				$this->$key = $value;
		}
	}

	public function setListingID($listing_id) {
		$this->listing_id = $listing_id;
	}

	function saveListingID($listing_id)
	{
	$db = new DatabaseQuery;
	$db->execute("	UPDATE photos
					SET listing_id=$listing_id
					WHERE photo_id=$this->photo_id");
	}

	function getPhotoID()
	{
	return $this->photo_id;
	}

	function displayForm() {
		global $user;

		$content .= "<form action=\"/en/listings/form_photo_proc/\" method=\"post\" enctype=\"multipart/form-data\"><fieldset>
		<legend>Upload photo</legend>
		<label for=\"file\">Select JPG</label><input class=\"file\" id=\"file\" type=\"hidden\" name=\"listing_id\" value=\"$this->listing_id\">
		<input type=\"file\" name=\"file\">
		<input class=\"submit\" type=\"submit\" value=\"Save\">
		</fieldset>
		</form>";
		return $content;
	}

	public function save($file) {
		global $user, $model;
		
		if (strpos($file['type'], 'jp')) {
			if ($this->photo_id == '') {
				$db = new DatabaseQuery;
				$db->execute("	INSERT INTO listings_photos (	listing_id,
																user_id,
																ts)
								VALUES (	$this->listing_id,
											".$user->getUserID().",
											NOW())");
				$this->photo_id = $db->getNewID();
			}
			
			$uploader = $model->tool('uploader');
		
			if ($uploader->exists('file')) {
				$uploader->setUploadFolder(IMAGE_STORE_FILEPATH . 'large/');
				if ($uploader->captureUpload('file')) {
					$file = $uploader->successful[0]['target'];
					if ($model->tool('image')->resize($file, 1200, 900, true)) {
						rename($file, $this->getLargePath());
						$item = new ListingsItem($this->listing_id);
						$item->squash();
					}
				}
			}
			/*
			if (is_uploaded_file($file['tmp_name'])) {
				$im = new ImageManipulator($file['tmp_name']);
				$im->resizeMaxDimension(600)->saveToFile($this->getLargePath(), false);
				
				$item = new ListingsItem($this->listing_id);
				$item->squash();
			}
			*/
		}
	}

	public function getLargePath() {
		return IMAGE_STORE_FILEPATH.'large/'.$this->photo_id.'.jpg';
	}

	public function delete() {
		@unlink($this->getLargePath());
		$db = new DatabaseQuery;
		$db->execute('	DELETE FROM listings_photos
						WHERE photo_id = '.$this->photo_id);
	}
}
?>