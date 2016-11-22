<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$photo = new Photo($_GET['photo_id']);
$photo->delete();

HTTP::redirect('index.php');
?>