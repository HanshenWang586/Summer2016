<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');
$pap = new AdminPage($admin_user);
$pap->setModuleKey('classifieds');

if (isset($_GET['classified_ids']))
	$classified_ids = $_GET['classified_ids'];
else
	$classified_ids = array($_GET['classified_id']);

foreach ($classified_ids as $classified_id) {
	$ci = new ClassifiedsItem($classified_id);
	switch ($_GET['action']) {
		case 'delete':
			$ci->deleteAdmin();
		break;

		case 'approve':
			$ci->setLive(true);
		break;

		case 'undelete':
			$ci->setLive();
		break;

		case 'always_approve':
			$ci->setLive();
			$ci->getUser()->setClassifiedsApproved();
		break;
	}
}

HTTP::redirect('list.php'.ContentCleaner::buildGetString(array('page' => $_GET['page'], 'ss' => $_GET['ss'])));
?>