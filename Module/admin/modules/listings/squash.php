<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$li = new ListingsItem($_GET['listing_id']);
$li->squash();

HTTP::redirect('squashing.php?page='.$_GET['page']);
?>