<?php
class GalleryControl {

	private $album_id;
	private $photographer_id;
	private $tags = array();

	public function __construct() {
		$this->id = microtime(true);
	}

	public function getNav() {
		$content .= FormHelper::open_ajax('gallery_nav_form');
		$f[] = FormHelper::select('Album', 'album_id', $this->getAlbumIDs(), $this->album_id, array('onchange' => 'runGalleryUpdate()'));
		$f[] = FormHelper::select('Photographer', 'photographer_id', $this->getPhotographerIDs(), $this->photographer_id, array('onchange' => 'runGalleryUpdate()'));
		//$f[] = FormHelper::input('Tag', 'ss', '');
		//$f[] = FormHelper::element('', '<div id="gallery_results"></div>');
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		$content .= $this->getTalkyBit();
		return $content;
	}

	public function setAlbumID($album_id) {
		$this->album_id = $album_id;
	}

	public function setPhotographerID($photographer_id) {
		$this->photographer_id = $photographer_id;
	}

	public function getPhotos() {
		global $site;
		$db = new DatabaseQuery;
		
		$tables[] = 'gallery_photos f';

		if (ctype_digit($this->album_id)) {
			$criteria[] = " album_id = $this->album_id ";
			$criteria[] = " p2a.photo_id = f.photo_id ";
			$tables[] = 'gallery_photos2album p2a';
		}

		if (ctype_digit($this->photographer_id))
			$criteria[] = " photographer_id = $this->photographer_id ";

		if (count($criteria)) {
			array_unshift($criteria, '');
			$sql = '	SELECT *
						FROM '.implode(', ', $tables).'
						WHERE 1 = 1
						'.(count($criteria) ? implode('AND ', $criteria) : '').'
						ORDER BY ts ASC';
		}
		else {
			$sql = '	SELECT *
						FROM '.implode(', ', $tables).'
						WHERE 1 = 1
						ORDER BY RAND()
						LIMIT 50';
			$this->random = 'random';
		}

		$rs = $db->execute($sql);
		$this->number_photos = $rs->getNum();
		
		//$content .= $sql;

		while ($row = $rs->getRow()) {
			$photo = new Photo;
			$photo->setData($row);
			$photos[] = $photo->getPublicThumbnail();
		}

		$content .= HTMLHelper::wrapArrayInUl($photos);
		return $content;
	}

	private function getAlbumIDs() {
		$pal = new PhotoAlbumList;
		$pal->setPhotographerID($this->photographer_id);
		return $pal->getAlbumIDs();
	}

	private function getPhotographerIDs() {
		$ppl = new PhotographerList;
		$ppl->setAlbumID($this->album_id);
		return $ppl->getPhotographerIDs();
	}

	private function getTalkyBit() {
		$content = "You're looking at <strong>$this->number_photos</strong> $this->random photos";

		if (ctype_digit($this->album_id) && $this->album_id != 0) {
			$album = new PhotoAlbum($this->album_id);
			$content .= " in the album <strong>".$album->getName()."</strong>";
		}

		if (ctype_digit($this->photographer_id)) {
			$photographer = new Photographer($this->photographer_id);
			$content .= " by the photographer <strong>".$photographer->getName()."</strong>";
		}

		$content .= '.';

		if (ctype_digit($this->album_id) || ctype_digit($this->photographer_id)) {
			$content .= ' <a href="/en/gallery/">Clear</a>';
		}

		return $content;
	}
}
?>