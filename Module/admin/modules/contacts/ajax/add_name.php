<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$contact = new Contact($_POST['contact_id']);
$name = new ContactName;
$name->setData($_POST);
$contact->addName($name);
?>