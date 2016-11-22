<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings_photos');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
				   FROM listings_photos p, listings_data i
				   WHERE i.listing_id = p.listing_id
				   '.(ctype_digit($_GET['listing_id']) ? "AND i.listing_id = {$_GET['listing_id']}" : '').'
				   ORDER BY ts DESC LIMIT 50');

$body .= '<table cellspacing="1" class="gen_table">';

while ($row = $rs->getRow()) {
	$lp = new ListingsPhoto;
	$lp->setData($row);
	$body .= '<tr>
	<td>'.$lp->getAdminThumbnail()."</td>
	<td>{$row['listing_id']}</td>
	<td><a href=\"index.php?listing_id={$row['listing_id']}\">{$row['name_en']}</a></td>
	<td>{$row['ts']}</td>
	<td><a href=\"delete.php?photo_id={$row['photo_id']}\" onclick=\"conf_del()\">Delete</a></td>
	</tr>";
}

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>