<?php
class GalleryPhoto {
	
	public function __construct($photo_id = '') {
		if (ctype_digit($photo_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
							   FROM gallery_photos
							   WHERE photo_id = $photo_id");
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
	
	public function setPhotoID($photo_id) {
		$this->photo_id = $photo_id;
	}
	
	public function setAlbumID($album_id) {
		$this->album_id = $album_id;
	}
	
	public function setPhotographerID($photographer_id) {
		$this->photographer_id = $photographer_id;
	}
	
	private function getPhotographer() {
		return new GalleryPhotographer($this->photographer_id);
	}
	
	private function getDateShot() {
		return DateManipulator::convertYMDToFriendly($this->ts, array('show_year' => true));
	}
	
	public function getThumbnail() {
		$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.'thumbnail/'.$this->photo_id.'.jpg');

		if ($size) {
			$hashstub .= ($this->album_id != '') ? 'album_id='.$this->album_id : '';
			$hashstub .= ($this->photographer_id != '') ? 'photographer_id='.$this->photographer_id : '';
			return "<a href=\"/en/gallery/#{$hashstub}&photo_id={$this->photo_id}\"><img src=\"".GALLERY_PHOTO_STORE_URL."thumbnail/$this->photo_id.jpg\" border=\"0\" {$size[3]} style=\"margin-top: ".((160 - $size[1])/2)."px;\"></a>";
		}
	}
	
	public function getLarge() {
		$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.'large/'.$this->photo_id.'.jpg');
		return "<div id=\"gallery_large\"><img src=\"".GALLERY_PHOTO_STORE_URL."large/$this->photo_id.jpg\" {$size[3]} border=\"0\" alt=\"*\"></div>";
	}
	
	public function getMeta() {
		$content .= $this->caption ? ContentCleaner::PWrap($this->caption) : '';
		$content .= ContentCleaner::PWrap('Photographer: '.$this->getPhotographer()->getLinkedName()
											.($this->ts != 0 ? '<br />Date shot: '.$this->getDateShot() : ''));
		return $content;
	}
	
	public function getAlbums() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM gallery_albums a, gallery_photos2album p2a
							WHERE a.album_id = p2a.album_id
							AND p2a.photo_id = $this->photo_id");
		
		while ($row = $rs->getRow()) {
			$items[] = "<a href=\"/en/gallery/#album_id={$row['album_id']}\">{$row['album']}</a>";
		}
		
		return HTMLHelper::wrapArrayInUl($items);
	}
}
?>