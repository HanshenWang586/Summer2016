<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('classifieds');

	$total = 0;
	$total_responses = 0;
	$i = 0;
	$db = new DatabaseQuery;
	$site = new Site();
	$max = 0;
	$tallies = array();
	// ever feel like you have too many loop variables?

	$rs = $db->execute("SELECT	COUNT(*) AS tally,
								SUM(responses) AS responses,
								DATE_FORMAT(ts, '%Y-%m') AS yearmonth
						FROM classifieds_data
						GROUP BY yearmonth
						ORDER BY yearmonth DESC");

	while ($row = $rs->getRow()) {
		$tallies[] = $row['tally'];
	}

	$rs->reset();
	$max = max($tallies);
	$min = min($tallies);
	$mean = round(array_sum($tallies)/count($tallies), 2);

	$body .= "<b>GoKunming</b><br />
	maximum: $max; minimum: $min; mean: $mean<br />
	<br />
	<table cellspacing=\"1\" class=\"gen_table\">
	<tr>
	<td><b>Year-Month</b></td>
	<td><b>Posted</b></td>
	<td><b>Responses</b></td>
	<td><b>Responses per post</b></td>
	</tr>";

	while ($row = $rs->getRow()) {
		$total += $row['tally'];
		$total_responses += $row['responses'];
		$body .= "<tr".($row['tally'] == $max ? " class=\"max\"" : '').">
		<td>{$row['yearmonth']}</td>
		<td>{$row['tally']}".(++$i == 1 ? " (".round($row['tally']*date('t')/date('j')).") " : '')."</td>
		<td>{$row['responses']}".($i == 1 ? " (".round($row['responses']*date('t')/date('j')).") " : '')."</td>
		<td>".round($row['responses']/$row['tally'], 2)."</td>
		</tr>";
	}

	$body .= "<tr><td>Total</td><td>$total</td><td>$total_responses</td><td></td></tr>
	</table><br /><br />";

$pap->setTag('main', $body);
$pap->output();
?>