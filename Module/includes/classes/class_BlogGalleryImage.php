<?php
class BlogGalleryImage {

	private $blog_gallery_id;
	private $blog_gallery_image_id;
	private $files;
	private $thumbnail_width = 80;
	private $thumbnail_height = 80;


	function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	function setFilesData($files) {
		$this->files = $files;
	}

	public function setBlogGalleryID($blog_gallery_id) {
		$this->blog_gallery_id = $blog_gallery_id;
	}

	public function getBlogGalleryID() {
		return $this->blog_gallery_id;
	}

	public function displayForm() {

		$content = FormHelper::open('form_blog_gallery_proc.php', array('file_upload' => true));
		$content .= FormHelper::hidden('blog_gallery_id', $this->blog_gallery_id);
		$content .= FormHelper::file('Image', 'file', array('guidetext' => 'JPG, sensible largest dimension'));
		$content .= FormHelper::submit('Save');
		$content .= FormHelper::close();

		return $content;
	}

	function save() {
		if (is_uploaded_file($this->files['file']['tmp_name'])) {

			$db = new DatabaseQuery;
			$db->execute("INSERT INTO blog_gallery_images (blog_gallery_id)
						 VALUES ($this->blog_gallery_id)");
			$this->blog_gallery_image_id = $db->getNewID();

			/*
			following lines replaced by ImageManipulator below
			move_uploaded_file($this->files['file']['tmp_name'], BLOG_GALLERY_STORE_FILEPATH.'large/'.$this->blog_gallery_image_id.'.jpg');

			$src_img = imagecreatefromjpeg(BLOG_GALLERY_STORE_FILEPATH.'large/'.$this->blog_gallery_image_id.'.jpg');
			$new_w = $this->thumbnail_width;
			$new_h = $this->thumbnail_height;

			$dst_img = imagecreatetruecolor($new_w, $new_h);
			imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, imagesx($src_img), imagesy($src_img));
			imagejpeg($dst_img, BLOG_GALLERY_STORE_FILEPATH.'thumbnails/'.$this->blog_gallery_image_id.'.jpg');
			imagedestroy($dst_img);
			*/
			
			$im = new ImageManipulator($this->files['file']['tmp_name']);
			$im->saveToFile(BLOG_GALLERY_STORE_FILEPATH.'large/'.$this->blog_gallery_image_id);
			
			$im = new ImageManipulator($this->files['file']['tmp_name']);
			$im->resize($this->thumbnail_width, $this->thumbnail_height)->saveToFile(BLOG_GALLERY_STORE_FILEPATH.'thumbnails/'.$this->blog_gallery_image_id);
		}
	}

	function getThumbnail() {
		$size = @getimagesize(BLOG_GALLERY_STORE_FILEPATH.'thumbnails/'.$this->blog_gallery_image_id.'.jpg');

		if ($size)
			$content = "<a href=\"".BLOG_GALLERY_STORE_URL.'large/'.$this->blog_gallery_image_id.".jpg\" rel=\"lightbox[item]\"><img src=\"".BLOG_GALLERY_STORE_URL.'thumbnails/'.$this->blog_gallery_image_id.".jpg\" {$size[3]}></a>";

		return $content;
	}
}
?>