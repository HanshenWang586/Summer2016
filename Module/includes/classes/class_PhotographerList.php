<?php
class PhotographerList {

	public function getSelect($photographer_id) {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   FROM gallery_photographers
						   ORDER BY photographer");
		$content .= "<select name=\"photographer_id\">";
		while ($row = $rs->getRow()) {
			$content .= "<option value=\"{$row['photographer_id']}\"".($photographer_id == $row['photographer_id'] ? ' selected' : '').">{$row['photographer']}</option>";
		}
		$content .= "</select>";
		return $content;
	}

	public function getPublicSelect() {
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT DISTINCT p.photographer_id, photographer
						   FROM gallery_photographers p, gallery_photos f
						   WHERE f.photographer_id = p.photographer_id
						   ORDER BY photographer");

		$content .= "<select name=\"photographer_id\" onchange=\"loadGalleryByPhotographer(this.value)\">
		<option value=\"\">Please select...</option>";

		while ($row = $rs->getRow()) {
			$content .= "<option value=\"{$row['photographer_id']}\"".($photographer_id == $row['photographer_id'] ? ' selected' : '').">{$row['photographer']}</option>";
		}
		$content .= '</select>';
		return $content;
	}

	public function setAlbumID($album_id) {
		$this->album_id = $album_id;
	}

	public function getPhotographerIDs() {
		global $site;
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

		return $photographers;
	}
}
?>