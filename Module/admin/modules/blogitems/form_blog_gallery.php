<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT * FROM blog_gallery_images WHERE blog_gallery_id={$_GET['blog_gallery_id']}");

while ($row = $rs->getRow())
	$body .= "<img src=\"".BLOG_GALLERY_STORE_URL."thumbnails/{$row['blog_gallery_image_id']}.jpg\"><br />";

$bgi = new BlogGalleryImage;
$bgi->setBlogGalleryID($_GET['blog_gallery_id']);
$body .= $bgi->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>