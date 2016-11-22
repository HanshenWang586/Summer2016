<?php
require($_SERVER['DOCUMENT_ROOT']."/admin/includes/functions.php");

$city = new City;
$city->setData($_POST);
$city->save();

HTTP::redirect('cities.php');
?>