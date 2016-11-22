<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('spider');

$pager = new AdminPager;
$pager->setLimit(30);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$data = "<table cellspacing=\"1\" class=\"gen_table\">
<tr>
<td><b>Search</b></td>
<td><b>Results</b></td>
<td><b>Timestamp</b></td>
<td><b>Session ID</b></td>
</tr>";

$rs = $pager->setSQL("	SELECT ss, results, ts, session_id
						FROM log_searches
						ORDER BY ts DESC");

	while ($row = $rs->getRow())
	{
	$data .= "<tr>
	<td>{$row['ss']}</td>
	<td>{$row['results']}</td>
	<td>{$row['ts']}</td>
	<td>{$row['session_id']}</td>
	</tr>";
	}

$data .= "</table>";

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>