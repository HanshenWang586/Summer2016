<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$city_ids = array(52,23,49, 6, 18, 48, 39, 19, 41, 46, 42, 44, 16,13,38, 30,61,35,50,26,47,36,33,17,7,56,45,4,54,28,20,31,15,32,24,37,21,53,22,14,25,55,57,27,51,8);

$db = new DatabaseQuery;
$rs = $db->execute('SELECT listing_id
				   FROM listings_data
				   WHERE city_id IN ('.implode(',', $city_ids).')');

while ($row = $rs->getRow()) {
	$rs_2 = $db->execute("DELETE FROM listings_i2c WHERE listing_id = {$row['listing_id']}");
	$rs_2 = $db->execute("DELETE FROM listings_reviews WHERE listing_id = {$row['listing_id']}");
	$rs_2 = $db->execute("DELETE FROM calendar_events WHERE listing_id = {$row['listing_id']}");
}

$rs = $db->execute('DELETE
					FROM listings_data
					WHERE city_id IN ('.implode(',', $city_ids).')');

$db->execute('update
			listings_cities
			set live= 0
			WHERE city_id IN ('.implode(',', $city_ids).')');
?>