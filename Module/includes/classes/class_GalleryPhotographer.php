<?php
class GalleryPhotographer {
	
	public function __construct($photographer_id = '') {
		if (ctype_digit($photographer_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
							   FROM gallery_photographers
							   WHERE photographer_id = $photographer_id");
			$this->setData($rs->getRow());
			
			$rs = $db->execute("SELECT photo_id
							   FROM gallery_photos p
							   WHERE photographer_id = $photographer_id
							   ORDER BY ts DESC");
			
			while ($row = $rs->getRow()) {
				$this->photo_ids[] = $row['photo_id'];
			}
		}
	}
	
	private function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}
	
	public function getTitle() {
		return "<h1 style=\"margin-bottom:10px;\"><a href=\"/en/gallery/\">Gallery</a> > <a href=\"/en/gallery/#photographer_id=$this->photographer_id\">$this->photographer</a></h1>";
	}
	
	public function getLinkedName() {
		$content = $this->photographer;
		if ($this->website != '') {
			$content = "<a href=\"$this->website\">$content</a>";
		}
		return $content;
	}
	
	public function getThumbnails() {
		foreach ($this->photo_ids as $photo_id) {
			$photo = new GalleryPhoto;
			$photo->setPhotoID($photo_id);
			$photo->setPhotographerID($this->photographer_id);
			$items[] = $photo->getThumbnail();
		}
		
		return '<div id="gallery_thumbs">'.HTMLHelper::wrapArrayInUl($items).'</div>';
	}
	
	private function getControls() {
		
		/*foreach ($this->photo_ids as $key => $photo_id) {
			if ($photo_id == $this->photo_id) {
				$prev = isset($this->photo_ids[$key - 1]) ? "<a href=\"#photographer_id=$this->photographer_id&photo_id={$this->photo_ids[$key - 1]}\">Prev</a>" : '';
				$next = isset($this->photo_ids[$key + 1]) ? "<a href=\"#photographer_id=$this->photographer_id&photo_id={$this->photo_ids[$key + 1]}\">Next</a>" : '';
			}
		}
		
		$controls = array($prev, $next);
		return '<div class="gallery_control">'.HTMLHelper::wrapArrayInUl($controls).'</div>';*/
		
		$num = count($this->photo_ids);
		
		$album = "<a href=\"#photographer_id=$this->photographer_id\">Photographer</a>";
		$first = "<a href=\"#photographer_id=$this->photographer_id&photo_id={$this->photo_ids[0]}\">First</a>";
		$last = "<a href=\"#photographer_id=$this->photographer_id&photo_id={$this->photo_ids[$num - 1]}\">Last</a>";
		
		foreach ($this->photo_ids as $key => $photo_id) {
			$i++;
			if ($photo_id == $this->photo_id) {
				$count = "$i of ".$num;
				$prev = isset($this->photo_ids[$key - 1]) ? "<a href=\"#photographer_id=$this->photographer_id&photo_id={$this->photo_ids[$key - 1]}\">Prev</a>" : '';
				$next = isset($this->photo_ids[$key + 1]) ? "<a href=\"#photographer_id=$this->photographer_id&photo_id={$this->photo_ids[$key + 1]}\">Next</a>" : '';
			}
		}
		
		$controls = array($last, $next, $count, $prev, $first, $album);
		return '<div class="gallery_control">'.HTMLHelper::wrapArrayInUl($controls).'</div>';
	}
	
	public function getPhoto($photo_id) {
		$this->photo_id = $photo_id;
		$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.'large/'.$photo_id.'.jpg');

		if ($size) {
			$photo = new GalleryPhoto($photo_id);
			$content .= $this->getControls();
			$content .= $photo->getLarge();
			$content .= $this->getControls();
			$content .= $photo->getMeta();
			return $content;
		}
	}
}
?>