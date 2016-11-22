<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ni = new BlogImage($_GET['image_id']);
$ni->delete();

HTTP::redirect('form_images.php?blog_id='.$ni->blog_id);
?>