<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$rs = $pager->setSQL("	SELECT *, FROM_UNIXTIME(ts) AS ts_friendly
						FROM php_sessions
						WHERE site_id = ".$admin_user->getSiteID()."
						ORDER BY ts DESC");

$data = "<table class=\"gen_table\" cellspacing=\"1\">
<tr>
<td><b>URL</b></td>
<td><b>Session ID</b></td>
<td><b>Time</b></td>
</tr>";

while ($row = $rs->getRow()) {
$data .= "	<tr>
			<td>{$row['url']}</td>
			<td>{$row['session_id']}</td>
			<td nowrap>{$row['ts_friendly']}</td>
			</tr>";
}

$data .= '</table>';

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>