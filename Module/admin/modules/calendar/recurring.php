<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('calendar');

$days = array(1 => 'Monday',
			  2 => 'Tuesday',
			  3 => 'Wednesday',
			  4 => 'Thursday',
			  5 => 'Friday',
			  6 => 'Saturday',
			  7 => 'Sunday');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT calendar_id, event_day, name_en, r.description, r.live, sidebar
				   FROM calendar_recurring r, listings_data d
				   WHERE r.listing_id = d.listing_id
				   ORDER BY event_day ASC");

$body = "<table cellspacing=\"1\" class=\"gen_table\">
<tr valign=\"top\">
<td><b>Day</b></td>
<td><b>Place</b></td>
<td><b>Description</b></td>
<td></td>
<td></td>
<td></td>
</tr>";

while ($row = $rs->getRow()) {
	$body .= "<tr valign=\"top\"".($row['live'] == 1 ? '' : ' class="fadeout"').">
	<td>{$days[$row['event_day']]}</td>
	<td>{$row['name_en']}</td>
	<td width=\"400\">".($row['sidebar'] ? '<b>'.$row['description'].'</b>' : $row['description'])."</td>
	<td><a href=\"form_recurring.php?calendar_id={$row['calendar_id']}\">Edit</a></td>
	<td><a href=\"toggle_recurring.php?calendar_id={$row['calendar_id']}\">Toggle</a></td>
	<td><a href=\"delete_recurring.php?calendar_id={$row['calendar_id']}\" onclick=\"return conf_del();\">Delete</a></td>
	</tr>";
}

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>
