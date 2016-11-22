<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$li = new ListingsItem($_POST['listing_id']);
$li->setAdminPendingAddCategory($_POST['category_id']);

HTTP::redirect("categories.php?listing_id=".$_POST['listing_id']);
?>