<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$review = new Review;
$review->setData($_POST);
$review->saveAdmin();

HTTP::redirect('reviews.php');
?>