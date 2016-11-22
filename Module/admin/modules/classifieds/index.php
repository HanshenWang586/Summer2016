<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('classifieds');
$cl = new ClassifiedsList;
$waiting = $cl->displayWaiting();

if ($waiting != '') {
	$body = FormHelper::open_ajax('form_waiting');
	$body .= "$waiting<p>With checked:
	<a href=\"javascript:void(null)\" onclick=\"processFormWaiting('delete');\">Delete</a>
	• <a href=\"javascript:void(null)\" onclick=\"processFormWaiting('approve');\">Approve</a>";
	$body .= FormHelper::close();
}
else
	$body .= 'Nothing waiting';

$pap->appendTitle(' > Waiting');
$pap->setTag('main', $body);
$pap->output();
?>