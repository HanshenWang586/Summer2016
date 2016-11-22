<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$contact = new Contact($_GET['contact_id']);
echo $contact->display();

?>