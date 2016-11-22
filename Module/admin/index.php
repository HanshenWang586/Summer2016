<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('home');

$body = "Welcome, $admin_user->given_name

<table width=\"400\">
<tr><td></td></tr>
</table>";

$pap->setTag('#MAIN#', $body);
$pap->output();
?>