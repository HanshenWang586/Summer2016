<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ni = new BlogImage;
$ni->save($_FILES, $_POST);

HTTP::redirect('form_images.php?blog_id='.$ni->blog_id);
?>