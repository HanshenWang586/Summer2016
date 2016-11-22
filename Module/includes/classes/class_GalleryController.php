<?php
class GalleryController {
	
	public function index() {
		$p = new Page;
		$p->setTag('page_title', 'Gallery');
		
		$p->setTag('scripts', '<script>window.onload = function() {
setInterval(pollHash, 100);
}

var recentHash;
function pollHash() {
	if (window.location.hash == recentHash)
		return;
	recentHash = window.location.hash;
	$(\'#gallery\').load(\'/en/gallery/ajax/\' + recentHash.replace(\'#\', \'?\'));
	$(\'body\').scrollTop(0);
	$(\'html\').scrollTop(0);
}
</script>');
		
		$body = '<div id="gallery">'.$this->getCore().'</div>';
		$p->setTag('main', $body);
		$p->output();
	}
	
	public function ajax() {
				
		if (isset($_GET['album_id']) && isset($_GET['photo_id'])) {
			$album = new GalleryAlbum($_GET['album_id']);
			echo $album->getTitle();
			echo $album->getPhoto($_GET['photo_id']);
		}
		else if (isset($_GET['photographer_id']) && isset($_GET['photo_id'])) {
			$photographer = new GalleryPhotographer($_GET['photographer_id']);
			echo $photographer->getTitle();
			echo $photographer->getPhoto($_GET['photo_id']);
		}
		else if (isset($_GET['album_id'])) { // no one photo selected
			$album = new GalleryAlbum($_GET['album_id']);
			echo $album->getTitle();
			echo $album->getPaginationControls($_GET['page']);
			echo $album->getThumbnails($_GET['page']);
			echo $album->getPaginationControls($_GET['page']);
		}
		else if (isset($_GET['photographer_id'])) {
			$photographer = new GalleryPhotographer($_GET['photographer_id']);
			echo $photographer->getTitle();
			echo $photographer->getThumbnails();
		}
		else if (isset($_GET['photo_id'])) {
			$photo = new GalleryPhoto($_GET['photo_id']);
			echo '<h1><a href="/en/gallery/">Gallery</a></h1>';
			echo $photo->getLarge();
			echo $photo->getAlbums();
			echo $photo->getMeta();
		}
		else {
			echo $this->getCore();
		}
	}

	private function getCore() {
		$body .= '<h1><a href="/en/gallery/">Gallery</a></h1>
		<div id="gallery_home">';
		
		$albumlist = new GalleryAlbumList;
		$body .= $albumlist->getAlbums();
		
		$body .= '</div>';
		
		//$body .= '</div><div style="float:left;width:50%;">';
		
		$photographerlist = new GalleryPhotographerList;
		$body .= '<h2>Photographers</h2>'.$photographerlist->getPhotographers();

		
		
		return $body;
	}

	public function photo() {
		// note that this is very similar to index()
		global $user;
		$photo = new Photo(func_get_arg(0));
		$p = new Page;
		$p->setTag('page_title', 'Gallery');
		$body = '<h1><a href="/en/gallery/">Gallery</a></h1>
		<div id="gallery">';

		$body .= $photo->getLarge();

		$gc = new GalleryControl;
		$body .= $this->get_core($gc);

		$body .= '</div>';
		$p->setTag('main', $body);
		$p->output();
	}

	public function update() {
		$gc = new GalleryControl;
		$gc->setAlbumID($_GET['album_id']);
		$gc->setPhotographerID($_GET['photographer_id']);
		$body .= $this->get_core($gc);
		echo $body;
	}

	private function get_core($gc) {
		$photos = $gc->getPhotos(); // sets values for nav
		$content .= '<div id="gallery_nav">'.$gc->getNav().'</div>';
		$content .= '<div id="gallery_thumbs">'.$photos.'</div>';
		return $content;
	}

	public function album() {
		if (func_num_args() > 0) {
			$plist = new PhotoList;
			$plist->setAlbumID(func_get_arg(0));
			echo $plist->getPublic();
		}
	}

	public function photographer() {
		if (func_num_args() > 0) {
			$plist = new PhotoList;
			$plist->setPhotographerID(func_get_arg(0));
			echo $plist->getPublic();
		}
	}

	public function tag() {
		if (func_num_args() > 0) {
			$plist = new PhotoList;
			$plist->setTag(urldecode(func_get_arg(0)));
			echo $plist->getPublic();
		}
	}

	public function tags() {
		$tags = array();
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT DISTINCT tag
							FROM photos_tags
							WHERE tag LIKE '%{$_GET['ss']}%'");

		while ($row = $rs->getRow()) {
			$tags[] = '<a href="javascript:void(null)" onclick="loadGalleryByTag(\''.$row['tag'].'\')">'.$row['tag'].'</a>';
		}

		echo HTMLHelper::wrapArrayInUl($tags);
	}
}
?>