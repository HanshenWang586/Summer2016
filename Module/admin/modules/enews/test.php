<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('enews');

$body .= "<form action=\"test_proc.php\" method=\"post\">
<input type=\"hidden\" name=\"enews_id\" value=\"{$_GET['enews_id']}\">
<textarea name=\"emails\" cols=\"65\" rows=\"20\">matthew@gokunming.com
chris@gokunming.com</textarea><br />
<br />

<input type=\"submit\" value=\"Send\">
</form>";

$pap->setTag('main', $body);
$pap->output();
?>