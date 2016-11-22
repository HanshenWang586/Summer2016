<?php
class BlogImageList {
	function __construct($blog_id = '') {
		$this->blog_id = $blog_id;
	}

	function setAdmin($admin) {
		$this->admin = $admin;
	}

	function display() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM blog_images
							WHERE blog_id=$this->blog_id
							ORDER BY ts DESC");
		
		if ($rs->getNum() == 0)
			$content .= 'No images';
		
		while ($row = $rs->getRow()) {
			$ni = new BlogImage;
			$ni->setAdmin($this->admin);
			$ni->setData($row);
			$content .= $ni->display();
		}
		
		return $content;
	}

	public function getAll($pager) {
		$rs = $pager->execute("SELECT * FROM blog_images ORDER BY blog_id DESC");
		
		while ($row = $rs->getRow()) {
			$bi = new BlogImage;
			$bi->setData($row);
			$images[] = $bi->getAdminThumbnail();		
		}
		
		return '<div id="blog_thumbnails">'.HTMLHelper::wrapArrayInUl($images).'</div>';
	}
}
?>