<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM contacts");

	while($row = $rs->getRow())
	{
	$contact = new Contact;
	$contact->setData($row);
	$contact->bundle();
	}

HTTP::redirect('index.php');
?>