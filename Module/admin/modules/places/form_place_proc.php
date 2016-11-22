<?php
require($_SERVER['DOCUMENT_ROOT']."/admin/includes/functions.php");

$place = new Place;
$place->setData($_POST);
$place->save();

HTTP::redirect();
?>