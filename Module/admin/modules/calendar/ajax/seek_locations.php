<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$links = array();

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM listings_data d, listings_cities c
					WHERE name_en LIKE '%".$db->clean($_GET['location_stub'])."%'
					AND status = 1
					AND d.city_id = c.city_id
					ORDER BY name_en");

	if ($rs->getNum()) {
		while ($row = $rs->getRow())
			$links[] = "<a href=\"javascript:void(null)\" onClick=\"calendarUseSuggestedLocation({$row['listing_id']});\">{$row['name_en']} ({$row['city_en']}: {$row['address_en']})</a>";

		echo HTMLHelper::wrapArrayInUl($links);
	}
	else
		echo 'No results found. Perhaps the venue isn\'t in the database?';
?>