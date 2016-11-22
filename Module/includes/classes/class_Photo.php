<?php
class Photo {

	public function __construct($photo_id = '') {
		if (ctype_digit($photo_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT * FROM photos WHERE photo_id = '.$photo_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($row) {
		if (is_array($row)) {
			foreach($row as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function setSiteID($site_id) {
		$this->site_id = $site_id;
	}

	public function setPhotographerID($photographer_id) {
		$this->photographer_id = $photographer_id;
	}

	public function setAlbumID($album_id) {
		$this->album_id = $album_id;
	}

	function getPhotoID()
	{
	return $this->photo_id;
	}

	function getTs()
	{
	return $this->ts;
	}

	function getFilesystemTs()
	{
	return date('Y-m-d H:i:s', filemtime(GALLERY_PHOTO_STORE_FILEPATH."large/$this->photo_id.jpg"));
	}

	public function setSource($file) {
		$this->source = $file;
	}

	public function save() {
		$db = new DatabaseQuery;
		if (ctype_digit($this->photo_id)) {
			$db->execute("	UPDATE photos
							SET site_id = $this->site_id,
								photographer_id = $this->photographer_id,
								album_id = $this->album_id
							WHERE photo_id = $this->photo_id");
		}
		else {
			$size = getimagesize($this->source);
			$db->execute("	INSERT INTO photos (	orientation,
													site_id,
													photographer_id,
													album_id,
													ts)
							VALUES (	'".($size[0] > $size[1] ? 'L' : 'P')."',
										$this->site_id,
										$this->photographer_id,
										$this->album_id,
										NOW())");
			$this->photo_id = $db->getNewID();
			$this->makeThumb();
			move_uploaded_file($this->source, GALLERY_PHOTO_STORE_FILEPATH.$this->photo_id.'.jpg');
		}

		$this->saveTags();
	}

	/**
	 * @deprecated
	 */
	public function displayUploadForm($x) {
		global $admin_user;

		$x = $x == '' ? 1 : $x;

		if (!ctype_digit($this->photo_id)) {
			$content = "<b>Specification</b><br />
					JPEG format<br />
					600px for largest dimension<br />
					<br />";
		}
		else {
			$content .= $this->getLarge();
		}

		$content .= "<form method=\"post\" action=\"form_upload_proc.php\" enctype=\"multipart/form-data\">
		<input type=\"hidden\" name=\"photo_id\" value=\"$this->photo_id\">

		<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">";

		if (!ctype_digit($this->photo_id)) {

			$content .= "
			<tr><td colspan=\"2\">I want to upload
			<select onChange=\"location.href='form_upload.php?x=' + this.value\">";

			for ($i=1; $i<10; $i++) {
				$content .= "<option value=\"$i\"".($i==$x ? ' selected' : '').">$i</option>";
			}

			$content .= "</select> file(s)</td></tr>";

			for ($i=0; $i<$x; $i++) {
				$content .= "<tr><td>File ".($i+1)."</td><td><input type=\"file\" name=\"file[$i]\" size=\"45\"></td></tr>";
			}
		}

		$content .= "<tr><td>Site</td><td>".$admin_user->getSiteIDSelect($this->site_id)."</td></tr>
		<tr><td>Photographer</td><td>".PhotographerList::getSelect($this->photographer_id)."</td></tr>
		<tr><td>Album</td><td>".PhotoAlbumList::getSelect($this->album_id)."</td></tr>
		<tr valign=\"top\"><td>Tags</td><td><textarea name=\"tags\">".$this->getTags()."</textarea></td></tr>";

		$content .= "<tr><td colspan=\"2\"><input type=\"submit\" value=\"Save\"></td></tr>
		</table>
		</form>";

		return $content;
	}

	function getThumbnail()
	{
	$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH."thumbnail/$this->photo_id.jpg");

		if ($size)
		{
		return "<img src=\"".GALLERY_PHOTO_STORE_URL."thumbnail/$this->photo_id.jpg\" {$size[3]}>";
		}
	}

	public function getLarge() {
		$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.$this->photo_id.'.jpg');

		if ($size) {
			return "<img src=\"".GALLERY_PHOTO_STORE_URL."$this->photo_id.jpg\" {$size[3]}>";
		}
	}

	function displayAdmin()
	{
	$content = $this->getLarge().'<br /><br />';
	$content .= $this->displayTagsForm();

	return $content;
	}

	function displayTagsForm()
	{
	$content = "<form action=\"form_photo_proc.php\" method=\"post\">
	<input type=\"hidden\" name=\"photo_id\" value=\"$this->photo_id\">
	<textarea name=\"tags\" rows=\"10\" cols=\"60\">".$this->getTags()."</textarea><br />
	<input type=\"submit\" value=\"Save\"><br />
	<form>";

	return $content;
	}

	private function saveTags() {
		$db = new DatabaseQuery;
		$db->execute('	DELETE FROM photos_tags
						WHERE photo_id = '.$this->photo_id);

		if ($this->tags != '') {
			$tags = explode(',', $this->tags);

			foreach($tags as $tag) {
				$tags_trimmed[] = trim($tag);
			}

		$tags = array_unique($tags_trimmed);

			if (is_array($tags)) {
				foreach ($tags as $tag) {
					if ($tag != '') {
					$db->execute("	INSERT INTO photos_tags (	photo_id,
																tag)
									VALUES (	$this->photo_id,
												'".$db->clean($tag)."')");
					}
				}
			}
		}
	}

	function getNumTags()
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT COUNT(*) AS num_tags
						FROM photos_tags
						WHERE photo_id=$this->photo_id");
	$row = $rs->getRow();
	return $row['num_tags'];
	}

	function getTagsPublic()
	{
		if (ctype_digit($this->photo_id))
		{
		$tags = array();
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT *
								FROM photos_tags
								WHERE photo_id=$this->photo_id
								ORDER BY tag");

			while ($row = $rs->getRow())
			{
			$tags[] = "<a href=\"/en/gallery/tag/".urlencode($row['tag'])."\">{$row['tag']}</a>";
			}

		$content = implode(', ', $tags);
		}

	return $content;
	}

	public function getTags() {
		if (ctype_digit($this->photo_id)) {
			$tags = array();
			$db = new DatabaseQuery;
			$rs = $db->execute("	SELECT *
									FROM photos_tags
									WHERE photo_id = $this->photo_id
									ORDER BY tag");

			while ($row = $rs->getRow()) {
				$tags[] = $row['tag'];
			}

			$content = implode(', ', $tags);
			return $content;
		}
	}

	function getAdminThumbnail()
	{
	$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH."thumbnail/$this->photo_id.jpg");

		if ($size)
		{
		return "<img src=\"".GALLERY_PHOTO_STORE_URL."thumbnail/$this->photo_id.jpg\" border=\"0\" {$size[3]}>";
		}
	}

	public function getPublicThumbnail() {
		$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH."thumbnail/$this->photo_id.jpg");

		if ($size) {
			return "<a href=\"/en/gallery/photo/$this->photo_id/\"><img src=\"".GALLERY_PHOTO_STORE_URL."thumbnail/$this->photo_id.jpg\" border=\"0\" {$size[3]}></a>";
		}
	}

	function delete()
	{
	@unlink(GALLERY_PHOTO_STORE_FILEPATH."$this->photo_id.jpg");
	@unlink(GALLERY_PHOTO_STORE_FILEPATH."thumbnail/$this->photo_id.jpg");

	$db = new DatabaseQuery;
	$db->execute("	DELETE FROM photos
					WHERE photo_id=$this->photo_id");
	$db->execute("	DELETE FROM photos_tags
					WHERE photo_id=$this->photo_id");
	}

	function displayWidth180()
	{
	$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH."width_180/$this->photo_id.jpg");

		if ($size)
		{
		return "<a href=\"/en/gallery/photo/$this->photo_id/\"><img src=\"".GALLERY_PHOTO_STORE_URL."width_180/$this->photo_id.jpg\" {$size[3]} alt=\"Click to view gallery\" /></a>";
		}
	}

	function displayLarge()
	{
	$size = @getimagesize(GALLERY_PHOTO_STORE_FILEPATH.'/'.$this->photo_id.'.jpg');

		if ($size)
		{
		return "<img src=\"".GALLERY_PHOTO_STORE_URL."/$this->photo_id.jpg\" {$size[3]} border=\"0\" alt=\"*\">";
		}
	}

	function displayPublic()
	{
	$content .= $this->displayLarge();
	$tags = $this->getTagsPublic();
	$content .= ($tags == '') ? '' : '<br /><br /><b>Tags:</b> '.$tags;

	return $content;
	}
}
?>