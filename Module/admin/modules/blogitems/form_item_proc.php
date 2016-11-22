<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$bi = new BlogItem;
$bi->setData($_POST);
$bi->save();

HTTP::redirect('index.php');
?>