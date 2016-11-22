<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$bgi = new BlogGalleryImage;
$bgi->setData($_POST);
$bgi->setFilesData($_FILES);
$bgi->save();

HTTP::redirect('form_blog_gallery.php?blog_gallery_id='.$bgi->getBlogGalleryID());
?>