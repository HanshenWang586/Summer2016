<?php
class BlogImage {
	private $align = 'center';

	function __construct($image_id = '') {
		if (ctype_digit($image_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM blog_images
								WHERE image_id = '.$image_id);
			$row = $rs->getRow();
			$this->setData($row);
		}
	}

	function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	function setAdmin($admin) {
		$this->admin = $admin;
	}

	public function getFilename() {
		return $this->image_id.'.'.$this->extension;
	}

	public function getAdminThumbnail() {
		return "<img src=\"".BLOG_PHOTO_STORE_URL."thumbnails/".$this->getFilename()."\" /><br />#".$this->image_id."#";
	}

	function display()
	{
	$imgdata = getimagesize(BLOG_PHOTO_STORE_FILEPATH.$this->getFilename());
	$content .= "<table cellspacing=\"1\" class=\"gen_table\">
	<tr valign=\"top\"><td>Code</td><td>#$this->image_id#</td>";

		if ($this->admin)
		{
		$content .= "	<td rowspan=\"7\"><a href=\"form_images.php?blog_id=$this->blog_id&image_id=$this->image_id\">Edit</a></td>
						<td rowspan=\"7\"><a href=\"delete_image.php?blog_id=$this->blog_id&image_id=$this->image_id\" onClick=\"return conf_del()\">Delete</a></td>";
		}

	$content .= sprintf("</tr>
	<tr valign=\"top\"><td>Image</td><td><img src=\"%s\"></td></tr>
	<tr valign=\"top\"><td>Caption</td><td>$this->caption</td>
	<tr valign=\"top\"><td>Type</td><td>$this->extension</td>
	<tr valign=\"top\"><td>Align</td><td>$this->align</td>
	<tr valign=\"top\"><td>Lightbox</td><td>%s</td></tr>
	<tr valign=\"top\"><td>Size</td><td>{$imgdata[0]} x {$imgdata[1]}</td></tr>
	</table><br />", $this->getURL(), $this->lightbox ? 'yes' : 'no');
	return $content;
	}

	public function getURL() {
		return '/en/blog/image/small/' . $this->getFilename();
	}

	public function getEmbeddable() {
		global $model;
		$image = BLOG_PHOTO_STORE_FILEPATH.$this->getFilename();
		$urlPath = '/en/blog/image/';
		
		if (file_exists($image)) {
			$size_original = getimagesize($image);
			$result = $model->tool('image')->resize($image, 673);
			$size = getimagesize($result);
			$caption = ContentCleaner::linkHashURLs($this->caption);
			$attrCaption = $caption ? htmlspecialchars(strip_tags($caption)) : '';
			
			$class = '';
			if ($size_original[0] >= 645) $class = ' class="img-645"';
			elseif ($size_original[0] >= 517) $class = ' class="img-517"';
			
			$lightbox = $this->lightbox and $size_original[0] > $size[0];
			$img = ' itemprop="image"';
			$thumb = ' itemprop="thumbnailUrl"';
			
			$content = sprintf('<img%s%s src="%ssmall/%s" alt="%s">', $lightbox ? $thumb : $img, $class, $urlPath, $this->getFilename(), $attrCaption);
			if ($lightbox) {
				$content = sprintf('<a%s href="%sbig/%s" title="%s" class="lightbox img">%s</a>', $lightbox ? $thumb : $img, $urlPath, $this->getFilename(), $attrCaption, $content);
			}
			if ($this->caption) $content .= '<div class="caption">'.ContentCleaner::wrapChinese($caption).'</div>';
			
			$style = $this->align != 'center' ? sprintf(' style="width: %spx;"', $size[0]) : '';
			$content = sprintf('<div%s class="%s">%s</div>', $style, $this->getClass(), $content);
		}
		else
			$content = "<span class=\"article_missing_image\">Sorry! There's an image missing... [$this->image_id]</span>";

		return $content;
	}

	private function getClass() {
		if (in_array($width, array(358, 368)))
			$content = 'blog_image_center';
		else
			$content = 'blog_image_'.$this->align;

		return $content;
	}

	public function displayForm($blog_id) {
	$content .= "<form action=\"form_image_proc.php\" method=\"post\" enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"blog_id\" value=\"".($this->blog_id=='' ? $blog_id : $this->blog_id)."\">
	<input type=\"hidden\" name=\"image_id\" value=\"$this->image_id\">
	<table cellspacing=\"1\" class=\"gen_table\">";

		if ($this->image_id!='')
		{
		$imgdata = getimagesize(BLOG_PHOTO_STORE_FILEPATH.$this->getFilename());
		$content .= "<tr valign=\"top\"><td>Current</td><td><img src=\"".BLOG_PHOTO_STORE_URL.$this->getFilename()."\" {$imgdata[3]}></td></tr>";
		}

	$content .= "<tr valign=\"top\"><td>Image</td><td><input type=\"file\" name=\"file\" size=\"45\"><br />Maximum width is <b>450</b> pixels<br />
Possible formats: JPG, GIF, PNG</td></tr>
	<tr valign=\"top\"><td>Caption</td><td><input name=\"caption\" value=\"" . htmlspecialchars($this->caption) . "\" size=\"45\"></td>
	<tr valign=\"top\"><td>Align</td><td>";

	$aligndata = array(	'name' => 'align',
						'values' => array(	'left'		=> 'Left',
											'center'	=> 'Centre',
											'right'		=> 'Right'),
						'existing' => $this->align);

	$content .= $this->write_radio($aligndata);
	$content .= "</td></tr>
	<tr valign=\"top\"><td>Lightbox</td><td>";

	$lightboxdata = array(	'name' => 'lightbox',
							'values' => array(	'1'		=> 'Yes',
												'0'		=> 'No'),
							'existing' => $this->lightbox);
	$content .= $this->write_radio($lightboxdata);
	$content .= "</td></tr>
	</table>
	<br />
	<input type=\"submit\" value=\"Save\">
	</form><br />";
	return $content;
	}

	function write_radio($data)
	{
	$content = "<table cellpadding=\"1\" cellspacing=\"0\">";
		foreach ($data['values'] as $value=>$display)
		{
		$content .= "<tr>
		<td><input type=\"radio\" name=\"{$data['name']}\" value=\"$value\"".($value==$data['existing'] ? ' checked' : '')."></td>
		<td>$display</td>
		</tr>";
		}
	$content .= "</table>";
	return $content;
	}

	public function save($files, $post) {
		$this->determineExtension($files['file']['type']);
		$this->caption = ContentCleaner::cleanForDatabase($post['caption']);

		$this->image_id = $post['image_id'];
		$this->blog_id = $post['blog_id'];
		$this->align = $post['align'];
		$this->lightbox = (int) $post['lightbox'];
		
		$db = new DatabaseQuery;
		
		
		
		if (ctype_digit($this->image_id)) {
			if ($this->extension != '') {
				$db->execute("	UPDATE blog_images
								SET align = '$this->align',
									extension = '$this->extension',
									lightbox = $this->lightbox,
									caption = '".$db->clean($this->caption)."',
									ts = NOW()
								WHERE image_id = $this->image_id");
			}
			else {
				$db->execute("	UPDATE blog_images
								SET align = '$this->align',
									lightbox = $this->lightbox,
									caption = '".$db->clean($this->caption)."',
									ts = NOW()
								WHERE image_id = $this->image_id");
			}
		}
		else {
			if ($this->extension != '') {
				$db->execute("	INSERT INTO blog_images (	blog_id,
															extension,
															align,
															lightbox,
															caption,
															ts)
								VALUES (	$this->blog_id,
											'$this->extension',
											'$this->align',
											$this->lightbox,
											'".$db->clean($this->caption)."',
											NOW())");
				$this->image_id = $db->getNewID();
			}
		}

		if (is_uploaded_file($files['file']['tmp_name']) && $this->image_id != '' && $this->extension != '') {
			$this->deletePreviousFiles();
			move_uploaded_file($files['file']['tmp_name'], BLOG_PHOTO_STORE_FILEPATH.$this->getFilename());
		}

		$bi = new BlogItem($this->blog_id);
		$bi->rebuild();
	}

	private function determineExtension($type) {
		$type = str_replace('image/', '', $type);

		switch ($type) {
			case 'jpeg':
			case 'pjpeg':
				$this->extension = 'jpg';
			break;

			case 'gif':
				$this->extension = 'gif';
			break;

			case 'png':
				$this->extension = 'png';
			break;
		}

	}

	public function delete() {
		if (unlink(BLOG_PHOTO_STORE_FILEPATH.$this->getFilename()) || !is_file(BLOG_PHOTO_STORE_FILEPATH.$this->getFilename())) {
			$db = new DatabaseQuery;
			$db->execute('	DELETE FROM blog_images
							WHERE image_id = '.$this->image_id);
		}
	}

	private function deletePreviousFiles() {
		@unlink(BLOG_PHOTO_STORE_FILEPATH.$this->image_id.'.gif');
		@unlink(BLOG_PHOTO_STORE_FILEPATH.$this->image_id.'.jpg');
		@unlink(BLOG_PHOTO_STORE_FILEPATH.$this->image_id.'.png');
	}

	private function makeThumbnail() {
		$filename = BLOG_PHOTO_STORE_FILEPATH.$this->getFilename();
		$im = new ImageManipulator(BLOG_PHOTO_STORE_FILEPATH.$this->getFilename());
		$im->resize(100,100)->setType($this->extension)->saveToFile(BLOG_PHOTO_STORE_FILEPATH.'thumbnails/'.$this->image_id);
	}
}
?>