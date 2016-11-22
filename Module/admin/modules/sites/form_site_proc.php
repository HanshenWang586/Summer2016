<?php
require($_SERVER['DOCUMENT_ROOT']."/admin/includes/functions.php");

$site = new Site;
$site->setData($_POST);
$site->save();

HTTP::redirect('index.php');
?>