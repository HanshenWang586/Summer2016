<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$li = new ListingsItem($_GET['listing_id']);
$li->abortPending();

HTTP::redirect("categories.php?listing_id=".$_GET['listing_id']);
?>