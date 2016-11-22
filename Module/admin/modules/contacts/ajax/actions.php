<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

switch($_GET['action']) {
	
	case 'note':
	$note = new ContactNote;
	$note->setContactID($_GET['contact_id']);
	echo $note->displayForm();
	break;
	
	case 'associate':
	$contact = new Contact;
	$contact->setContactID($_GET['contact_id']);
	echo $contact->displayAssociateForm();
	break;
	
	case 'callback':
	$todo = new ContactTodo($_GET['existing_id']);
	$todo->setContactID($_GET['contact_id']);
	$todo->setType('callback');
	echo $todo->displayForm();
	break;
	
	case 'coords.address':
	$coord = new ContactCoord($_GET['existing_id']);
	$coord->setContactID($_GET['contact_id']);
	echo $coord->displayForm('address');
	break;
	
	case 'coords.email':
	$coord = new ContactCoord($_GET['existing_id']);
	$coord->setContactID($_GET['contact_id']);
	echo $coord->displayForm('email');
	break;
	
	case 'coords.website':
	$coord = new ContactCoord($_GET['existing_id']);
	$coord->setContactID($_GET['contact_id']);
	echo $coord->displayForm('website');
	break;
	
	case 'coords.mobile':
	$coord = new ContactCoord($_GET['existing_id']);
	$coord->setContactID($_GET['contact_id']);
	echo $coord->displayForm('mobile');
	break;

	case 'coords.fixed':
	$coord = new ContactCoord($_GET['existing_id']);
	$coord->setContactID($_GET['contact_id']);
	echo $coord->displayForm('fixed');
	break;
	
	case 'coords.fax':
	$coord = new ContactCoord($_GET['existing_id']);
	$coord->setContactID($_GET['contact_id']);
	echo $coord->displayForm('fax');
	break;
	
	case 'name.name_en':
	$name = new ContactName($_GET['existing_id']);
	$name->setContactID($_GET['contact_id']);
	echo $name->displayForm('name_en');
	break;
	
	case 'name.name_zh':
	$name = new ContactName($_GET['existing_id']);
	$name->setContactID($_GET['contact_id']);
	echo $name->displayForm('name_zh');
	break;
	
	case 'name.nickname_en':
	$name = new ContactName($_GET['existing_id']);
	$name->setContactID($_GET['contact_id']);
	echo $name->displayForm('nickname_en');
	break;
	
	case 'name.nickname_zh':
	$name = new ContactName($_GET['existing_id']);
	$name->setContactID($_GET['contact_id']);
	echo $name->displayForm('nickname_zh');
	break;
	
	case 'name.organisation_en':
	$name = new ContactName($_GET['existing_id']);
	$name->setContactID($_GET['contact_id']);
	echo $name->displayForm('organisation_en');
	break;
	
	case 'name.organisation_zh':
	$name = new ContactName($_GET['existing_id']);
	$name->setContactID($_GET['contact_id']);
	echo $name->displayForm('organisation_zh');
	break;
}
?>