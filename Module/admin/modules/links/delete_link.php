<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ll = new LinksLink($_GET['link_id']);
$ll->delete();

HTTP::redirect("index.php?folder_id=$ll->folder_id");
?>