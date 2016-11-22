<?php
class PhotoAlbumList {

	public function setPhotographerID($photographer_id) {
		$this->photographer_id = $photographer_id;
	}

	public function getSelect($album_id) {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT album_id, CONCAT(site_name, ' > ', album) AS name
						   FROM gallery_albums a, sites s
						   WHERE s.site_id = a.site_id
						   ORDER BY name");

		$content .= "<select name=\"album_id\">
		<option value=\"0\">[no album]</option>";

		while ($row = $rs->getRow()) {
			$content .= "<option value=\"{$row['album_id']}\"".($album_id == $row['album_id'] ? ' selected' : '').">{$row['name']}</option>";
		}
		
		$content .= '</select>';
		return $content;
	}

	public function getPublicSelect() {
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   FROM gallery_albums
						   WHERE site_id = ".$site->getSiteID()."
						   ORDER BY album");

		$content .= "<select name=\"album_id\" onchange=\"loadGalleryByAlbum(this.value)\">
		<option value=\"\">Please select...</option>";

		while ($row = $rs->getRow()) {
			$content .= "<option value=\"{$row['album_id']}\"".($album_id == $row['album_id'] ? ' selected' : '').">{$row['album']}</option>";
		}
		$content .= '</select>';
		return $content;
	}

	public function getAlbumIDs() {
		global $site;
		$db = new DatabaseQuery;

		if (isset($this->photographer_id)) {
			$rs = $db->execute("SELECT *
								FROM gallery_albums a, gallery_photos p, gallery_photos2album p2a
								WHERE a.site_id = ".$site->getSiteID()."
								AND a.album_id = p2a.album_id
								AND p.photo_id = p2a.photo_id
								".(ctype_digit($this->photographer_id) ? "AND p.photographer_id = $this->photographer_id" : '')."
								ORDER BY album");
		}
		else {
			$rs = $db->execute("SELECT *
								FROM gallery_albums
								WHERE site_id = ".$site->getSiteID()."
								ORDER BY album");
		}

		$albums[''] = 'All albums';

		while ($row = $rs->getRow()) {
			$albums[$row['album_id']] = $row['album'];
		}

		return $albums;
	}

	public function getPublicTagSearch() {
		return "<input type=\"text\" onkeyup=\"loadGalleryTags(this.value)\" />";
	}
}
?>