<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$lc = new ListingsCategory($_GET['category_id']);
$lc->delete();

HTTP::redirect("index.php");
?>