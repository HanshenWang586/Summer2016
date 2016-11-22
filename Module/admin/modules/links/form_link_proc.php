<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$li = new LinksLink;
$li->setData($_POST);
$li->save();

HTTP::redirect("index.php?folder_id=$li->folder_id");
?>