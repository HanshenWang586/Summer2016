<?php
class PhotoAlbum {

	public function __construct($album_id = '') {
		if (ctype_digit($album_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
							   FROM gallery_albums
							   WHERE album_id = '.$album_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getName() {
		return $this->album;
	}
}
?>