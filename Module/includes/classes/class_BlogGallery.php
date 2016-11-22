<?php
class BlogGallery {

	private $blog_gallery_id;

	public function __construct($blog_gallery_id = '') {
		$this->blog_gallery_id = $blog_gallery_id;
	}

	public function getEmbeddable() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM blog_gallery_images
							WHERE blog_gallery_id = $this->blog_gallery_id");

		while ($row = $rs->getRow()) {
			$bgi = new BlogGalleryImage;
			$bgi->setData($row);
			$thumbnails[] = $bgi->getThumbnail();
		}

		if (count($thumbnails)) {
			return '<div class="blog_gallery">'.
			HTMLHelper::wrapArrayInUl($thumbnails).
			'</div><script type="text/javascript">$(function() {$(".blog_gallery a").lightBox({fixedNavigation:true});});</script>';
		}

		return $content;
	}
}
?>