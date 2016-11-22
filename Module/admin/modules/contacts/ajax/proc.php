<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

	switch($_POST['action']) {
	
		case 'add_note':
		$note = new ContactNote;
		$note->setUserID($admin_user->getUserID());
		$note->setData($_POST);
		$note->save();
		break;
		
		case 'add_todo':
		$todo = new ContactTodo;
		$todo->setData($_POST);
		$todo->save();
		break;
		
		case 'add_email':
		case 'add_website':
		case 'add_mobile':
		case 'add_fixed':
		case 'add_fax':
		$coord = new ContactCoord;
		$coord->setData($_POST);
		$coord->save();
		break;
		
		case 'add_address':
		$coord = new ContactCoord;
		$coord->setData($_POST);
		$coord->saveAddress();
		break;
		
		case 'name_en':
		case 'name_zh':
		case 'nickname_en':
		case 'nickname_zh':
		case 'organisation_en':
		case 'organisation_zh':
		$name = new ContactName;
		$name->setData($_POST);
		$name->save();
		break;
		
		case 'add_associates':
		$contact = new Contact;
		$contact->setContactID($_POST['contact_id']);
		
			foreach ($_POST['contact_ids'] as $contact_id) {
			$c = new Contact($contact_id);
			$contact->addAssociate($c);
			}
		break;
	}
	
$note = new ContactNote;
$note->setContactID($_POST['contact_id']);
echo $note->displayForm();
?>