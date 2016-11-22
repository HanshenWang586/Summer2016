<?php
class GalleryAlbumList {

	public function getAlbums() {
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   FROM gallery_albums
						   WHERE site_id = ".$site->getSiteID()."
						   ORDER BY ts DESC, album");

		while ($row = $rs->getRow()) {
			$rs_2 = $db->execute("SELECT p.photo_id
									FROM gallery_photos p, gallery_photos2album p2a
									WHERE p.photo_id = p2a.photo_id
									AND p2a.album_id = {$row['album_id']}
									ORDER BY ts ASC
									LIMIT 1");
			$row_2 = $rs_2->getRow();
			$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.'thumbnail/'.$row_2['photo_id'].'.jpg');
			$items[] = "<a href=\"/en/gallery/#album_id={$row['album_id']}\"><img src=\"".GALLERY_PHOTO_STORE_URL."thumbnail/{$row_2['photo_id']}.jpg\" border=\"0\" {$size[3]} style=\"margin-top: ".((210 - $size[1])/2)."px;\"><br />{$row['album']}</a>";
		}
		
		return HTMLHelper::wrapArrayInUl($items);
	}
}
?>