<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('classifieds');

$cfl = new ClassifiedsFolderList;
$folders = $cfl->getFolders();

$body = "<table class=\"gen_table\" cellspacing=\"1\">
<tr><td>Path</td><td>GoK Subscriptions</td></tr>";

foreach ($folders as $folder_id => $path) {
	$folder_id = (string) $folder_id;
	$folder = new ClassifiedsFolder($folder_id);
	$body .= "<tr><td>$path</td><td>".$folder->getNumSubscriptions()."</td></tr>";
}

$body .= "</table>";

$pap->setTag('main', $body);
$pap->output();
?>