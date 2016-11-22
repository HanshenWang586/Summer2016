<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$lp = new ListingsPhoto($_GET['photo_id']);
$lp->delete();

HTTP::redirect('index.php');
?>