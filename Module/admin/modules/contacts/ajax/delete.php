<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

	switch($_GET['action']) {
		
		case 'coords.email':
		case 'coords.website':
		case 'coords.address':
		case 'coords.mobile':
		case 'coords.fixed':
		case 'coords.fax':
		$coord = new ContactCoord($_GET['existing_id']);
		$coord->delete();
		break;
		
		case 'name':
		$name = new ContactName($_GET['existing_id']);
		$name->delete();
		break;
	}
?>