<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->set_menu_id(10);
$pap->setTitle('Listings');

$li = new ListingsItem($_GET['listing_id']);

	if ($_GET['listing_id']=='' && $_SESSION['last_added_city_id']!='')
	{
	$li->setCityID($_SESSION['last_added_city_id']);
	}
	
$body = $li->display_form();

$pap->setTag('main', $body);
$pap->output();
?>