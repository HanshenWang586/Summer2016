<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings_photos');

$body = "This page is expecting a KML file denoting a collection of points,
each point having latitude, longitude and one or more URL-refered photos.<br />
<br />
<form action=\"form_kml_proc.php\" method=\"post\" enctype=\"multipart/form-data\">
<input type=\"file\" name=\"kml\"> <input type=\"submit\" value=\"Send\">
</form>";

$pap->setTag('main', $body);
$pap->output();
?>