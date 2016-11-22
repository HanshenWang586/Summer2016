<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT DISTINCT DATE_FORMAT(ts, '%Y-%m') AS yearmonth
					FROM blog_comments
					ORDER BY yearmonth DESC");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr>
<td><b>Year-Month</b></td>
<td><b>GoKunming</b></td>
</tr>";

	while ($row = $rs->getRow()) {
		++$i;
		$tallies = array(0, 0, 0);

		$rs_2 = $db->execute("	SELECT COUNT(*) AS tally
								FROM blog_comments c
								WHERE c.ts LIKE '{$row['yearmonth']}%'
								AND c.live = 1");

		$tally = (int) $rs_2->getRow();
		$total +=  $tally;


		$body .= "<tr>
		<td>{$row['yearmonth']}</td>
		<td>{$tally}".($i == 1 ? " (".round($tally*date('t')/date('j')).") " : '')."</td>
		</tr>";
	}

$body .= "<tr>
<td>Total</td>
<td>{$total}</td>
</tr>
</table>";

$pap->setTag('main', $body);
$pap->output();
?>