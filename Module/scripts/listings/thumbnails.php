<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$db = new DatabaseQuery;
$rs = $db->execute('SELECT * FROM listings_photos');

while ($row = $rs->getRow()) {
	print_r($row);
	$filename = IMAGE_STORE_FILEPATH.'large/'.$row['photo_id'].'.jpg';
	if (file_exists($filename)) {
		$im = new ImageManipulator($filename);
		$im->getInfo();
		$im->resize(100, 100)->saveToFile(IMAGE_STORE_FILEPATH.'thumbnail/'.$row['photo_id'].'.jpg', false);
	}
}
echo 'done';
?>