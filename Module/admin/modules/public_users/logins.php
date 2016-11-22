<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$rs = $pager->setSQL("	SELECT u.user_id, nickname, method, l.ip, ts, session_id
						FROM log_logins l, public_users u
						WHERE u.user_id=l.user_id
						AND l.site_id = ".$admin_user->getSiteID()."
						ORDER BY ts DESC");

$data = "<table class=\"gen_table\" cellspacing=\"1\">
<tr>
<td><b>User ID</b></td>
<td><b>Nickname</b></td>
<td><b>IP</b></td>
<td><b>Session ID</b></td>
<td><b>Login time</b></td>
</tr>";

	while ($row = $rs->getRow())
	{
	$data .= "	<tr>
				<td>{$row['user_id']}</td>
				<td>{$row['nickname']}</td>
				<td>{$row['ip']}</td>
				<td>{$row['session_id']}</td>
				<td>{$row['ts']}</td>
				</tr>";
	}
$data .= '</table>';

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>