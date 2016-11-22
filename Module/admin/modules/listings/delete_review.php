<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ci = new Review($_GET['review_id']);
$ci->delete();

HTTP::redirect('reviews.php');
?>