<?php
class GalleryPhotographerList {

	public function getPhotographers() {
		/*global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT DISTINCT p.photographer_id, photographer
						   FROM gallery_photographers p, gallery_photos f".(ctype_digit($this->album_id) ? ', gallery_photos2album p2a' : '')."
						   WHERE f.photographer_id = p.photographer_id
						   ".(ctype_digit($this->album_id) ? "AND p2a.photo_id = f.photo_id AND p2a.album_id = $this->album_id" : '')."
						   ORDER BY photographer");

		$photographers[''] = 'All photographers';

		while ($row = $rs->getRow()) {
			$photographers[$row['photographer_id']] = $row['photographer'];
		}

		return $photographers;*/
	
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   FROM gallery_photographers p, gallery_photographers2site p2s
						   WHERE site_id = ".$site->getSiteID()."
						   AND p.photographer_id = p2s.photographer_id
						   ORDER BY photographer");

		while ($row = $rs->getRow()) {
			$items[] = "<a href=\"/en/gallery/#photographer_id={$row['photographer_id']}\">{$row['photographer']}</a>";
		}
		
		return HTMLHelper::wrapArrayInUl($items);
	}
}
?>