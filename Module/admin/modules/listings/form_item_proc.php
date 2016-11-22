<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$li = new ListingsItem;
$li->setData($_POST);
$li->save();

$_SESSION['last_added_city_id'] = $_POST['city_id'];

HTTP::redirect("categories.php?listing_id=".$li->getListingID());
?>