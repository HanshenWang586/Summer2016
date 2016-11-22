<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$contact = new Contact;
$contact->setData($_POST);
$contact->save();

HTTP::redirect('contact.php?contact_id='.$contact->getContactID());
?>