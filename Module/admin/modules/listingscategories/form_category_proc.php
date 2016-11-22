<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$lc = new ListingsCategory;
$lc->setData($_POST);
$lc->save();

HTTP::redirect("index.php");
?>