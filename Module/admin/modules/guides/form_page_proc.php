<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$guide = new Guide($_POST['guide_id']);
$guide->savePage($_POST['page'], $_POST['title'], $_POST['content']);

HTTP::redirect("index.php");
?>