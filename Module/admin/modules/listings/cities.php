<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT c.*, COUNT(*) AS tally
					FROM listings_data d, listings_cities c
					WHERE status=1
					AND c.city_id=d.city_id
					GROUP BY c.city_id
					ORDER BY city_en");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr>
<td><b>City (E)</b></td>
<td><b>City (C)</b></td>
<td><b>Number of<br />
listings</b></td>
<td><b>Phone code</b></td>
<td></td>
</tr>";

while ($row = $rs->getRow()) {
	$body .= "<tr>
	<td>{$row['city_en']}</td>
	<td class=\"chinese\">{$row['city_zh']}</td>
	<td>{$row['tally']}</td>
	<td>{$row['phone_code']}</td>
	<td><a href=\"index.php?city_id={$row['city_id']}\">Listings</a></td>
	</tr>";
}
	
$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>