<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings_photos');

$rs = execute("SELECT * FROM listings_photos");
		
	while ($row = get_row($rs))
	{
	$photo = new Photo;
	$photo->setData($row);
	$body .= "Photo ID: ".$photo->getPhotoID()."<br />";
	$body .= "Seeking location for $file<br />";
	$latlon = $photo->seekGPS();
	$body .= "Location updated to $latlon<br />";
	}

$pap->setTag('main', $body);
$pap->output();
?>