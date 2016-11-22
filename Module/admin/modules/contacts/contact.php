<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$p = new AdminPage($admin_user);
$p->setModuleKey('contacts');

$contact = new Contact($_GET['contact_id']);
$body = "<div id=\"contact\">".$contact->display()."</div>";
$body .= "<div style=\"float: left; width:300px; margin-left:30px;\">".$contact->getActionPanel()."</div>";

$p->setTag('main', $body);
$p->output();
?>