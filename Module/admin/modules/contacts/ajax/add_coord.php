<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$contact = new Contact($_POST['contact_id']);
$coord = new ContactCoord;
$coord->setData($_POST);
$contact->addCoord($coord);
?>