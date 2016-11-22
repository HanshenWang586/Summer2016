<?php
class GalleryAlbum {
	
	private $page_limit = 12;
	
	
	public function __construct($album_id = '') {
		if (ctype_digit($album_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
							   FROM gallery_albums
							   WHERE album_id = $album_id");
			$this->setData($rs->getRow());
			
			$rs = $db->execute("SELECT p.photo_id
							   FROM gallery_photos2album p2a, gallery_photos p
							   WHERE album_id = $this->album_id
							   AND p2a.photo_id = p.photo_id
							   ORDER BY ts ASC");
			
			while ($row = $rs->getRow())
				$this->photo_ids[] = $row['photo_id'];
		}
	}
	
	private function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}
	
	public function getThumbnails($page) {
		
		$page = $page ? $page : 1;
		$photo_ids = array_slice($this->photo_ids, ($page - 1) * $this->page_limit, $this->page_limit);
		
		foreach ($photo_ids as $photo_id) {
			$photo = new GalleryPhoto;
			$photo->setPhotoID($photo_id);
			$photo->setAlbumID($this->album_id);
			$items[] = $photo->getThumbnail();
		}
		
		return '<div id="gallery_thumbs">'.HTMLHelper::wrapArrayInUl($items).'</div>';
	}
	
	public function getPaginationControls($page) {
		
		$page = $page ? $page : 1;
		
		$num = count($this->photo_ids);
		$total_pages = ceil($num / $this->page_limit);
		
		if ($total_pages > 1) {
			if ($page != 1)
				$first = "<a href=\"#album_id=$this->album_id&page=1\">First</a>";
			
			if ($page != $total_pages)
				$last = "<a href=\"#album_id=$this->album_id&page=$total_pages\">Last</a>";
				
			$count = "Page $page of $total_pages";
			$prev = $page > 1 ? "<a class=\"arrow_left\" href=\"#album_id=$this->album_id&page=".($page - 1)."\">Prev</a>" : '';
			$next = $page < $total_pages ? "<a class=\"arrow_right\" href=\"#album_id=$this->album_id&page=".($page + 1)."\">Next</a>" : '';
			
			$controls = array($last, $next, $count, $prev, $first);
			return '<div class="gallery_control">'.HTMLHelper::wrapArrayInUl($controls).'</div>';
		}
	}
	
	private function getControls($photo_id) {
		
		$num = count($this->photo_ids);
		
		$album = "<a href=\"#album_id=$this->album_id\">Album</a>";
		
		if ($photo_id != $this->photo_ids[0])
			$first = "<a href=\"#album_id=$this->album_id&photo_id={$this->photo_ids[0]}\">First</a>";
			
		if ($photo_id != $this->photo_ids[$num - 1])
		$last = "<a href=\"#album_id=$this->album_id&photo_id={$this->photo_ids[$num - 1]}\">Last</a>";
		
		foreach ($this->photo_ids as $key => $photo_id) {
			$i++;
			if ($photo_id == $this->photo_id) {
				$count = "$i of ".$num;
				$prev = isset($this->photo_ids[$key - 1]) ? "<a class=\"arrow_left\" href=\"#album_id=$this->album_id&photo_id={$this->photo_ids[$key - 1]}\">Prev</a>" : '';
				$next = isset($this->photo_ids[$key + 1]) ? "<a class=\"arrow_right\" href=\"#album_id=$this->album_id&photo_id={$this->photo_ids[$key + 1]}\">Next</a>" : '';
			}
		}
		
		$controls = array($last, $next, $count, $prev, $first, $album);
		return '<div class="gallery_control">'.HTMLHelper::wrapArrayInUl($controls).'</div>';
	}
	
	public function getTitle() {
		return "<h1 style=\"margin-bottom:10px;\"><a href=\"/en/gallery/\">Gallery</a> > <a href=\"/en/gallery/#album_id=$this->album_id\">$this->album</a></h1>";
	}
	
	public function getPhoto($photo_id) {
		$this->photo_id = $photo_id;
		$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.'large/'.$photo_id.'.jpg');

		if ($size) {
			$photo = new GalleryPhoto($photo_id);
			$content .= $this->getControls($photo_id);
			$content .= $photo->getLarge();
			$content .= $this->getControls($photo_id);
			$content .= $photo->getMeta();
			return $content;
		}
	}
}
?>