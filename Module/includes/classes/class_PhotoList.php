<?php
class PhotoList {

	public function displayAdmin($pager) {
		$rs = $pager->setSQL("	SELECT *
								FROM photos p, photos_photographers f
								WHERE f.photographer_id = p.photographer_id
								ORDER BY ts DESC, photo_id DESC");

		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Photo</b></td>
		<td><b>Photographer</b></td>
		<td><b>Tags</b></td>
		<td><b>Time uploaded</b></td>
		<td></td>
		<td></td>
		</tr>";

		while ($row = $rs->getRow()) {
			$photo = new Photo;
			$photo->setData($row);
			$link = "form_upload.php?photo_id=".$photo->getPhotoID();

			$content .= "<tr valign=\"top\">
			<td><a href=\"$link\">".$photo->getAdminThumbnail()."</a></td>
			<td>{$row['photographer']}</td>
			<td width=\"300\">".$photo->getTags()."</td>
			<td>".$photo->getTs()."</td>
			<td><a href=\"$link\">Edit</a></td>
			<td><a href=\"delete_photo.php?photo_id=".$photo->getPhotoID()."\" onClick=\"return conf_del()\">Delete</a></td>
			</tr>";
		}

	$content .= "</table>";
	return $content;
	}

	function getTaglessPhotoID()
	{
	$rs = $db->execute("SELECT photo_id
						FROM photos
						ORDER BY RAND()");
	$row = $rs->getRow();

	$photo = new Photo($row['photo_id']);

		if ($photo->getNumTags() == 0)
		{
		return $row['photo_id'];
		}
		else
		{
		return $this->getTaglessPhotoID();
		}
	}

	public function getRandomPhotoID() {
		global $site;
		$db = new DatabaseQuery;

		if ($this->tag == '') {
			$rs = $db->execute('	SELECT photo_id
									FROM photos
									WHERE site_id='.$site->getSiteID().'
									ORDER BY RAND()
									LIMIT 1');
		}
		else
		{
		$rs = $db->execute("	SELECT p.photo_id
								FROM photos p, photos_tags t
								WHERE p.photo_id=t.photo_id
								AND site_id=".$site->getSiteID()."
								AND tag='".$db->clean($this->tag)."'
								ORDER BY RAND()
								LIMIT 1");
		}

	$row = $rs->getRow();
	return $row['photo_id'];
	}

	public static function getRandomLandscapePhotoIDs($number = 1) {
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute('	SELECT p.photo_id
								FROM gallery_photos p, gallery_albums a, gallery_photos2album p2a
								WHERE site_id = '.$site->getSiteID().'
								AND p.photo_id = p2a.photo_id
								AND p2a.album_id = a.album_id
								AND orientation = \'L\'
								AND homepage = 1
								ORDER BY RAND()
								LIMIT '.$number);
		
		while ($row = $rs->getRow()) {
			$image_ids[] = $row['photo_id'];
		}

		return $image_ids;
	}

	public function getNav() {
		/*
		$_SESSION['gallery_nav'] = array(	'photographer_id' => <>,
											'album_id' => <>,
											'tags' => array());


		*/

		$content .= FormHelper::open_ajax('gallery_nav_form');
		$f[] = FormHelper::element('Album', PhotoAlbumList::getPublicSelect());
		$f[] = FormHelper::element('Photographer', PhotographerList::getPublicSelect());
		$f[] = FormHelper::element('Album', PhotoAlbumList::getPublicTagSearch());
		$f[] = FormHelper::element('', '<div id="gallery_results"></div>');
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();

		/*
		$content = '<p>Album '.PhotoAlbumList::getPublicSelect().'</p>';
		$content .= '<p>Photographer '.PhotographerList::getPublicSelect().'</p>';
		$content .= '<p>Tags '.PhotoAlbumList::getPublicTagSearch().'</p>';
		$content .= '<div id="gallery_results"></div>';*/
		return $content;
	}

	public function setTag($tag) {
		$this->tag = urldecode($tag);
	}

	public function setAlbumID($album_id) {
		$this->album_id = $album_id;
	}

	public function setPhotographerID($photographer_id) {
		$this->photographer_id = $photographer_id;
	}
}
?>
