<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

echo "<input id=\"location\" onkeyup=\"calendarSuggestLocations()\"><br />
<div id=\"suggested_locations\" style=\"margin-left:130px;\"></div>
<div id=\"suggested_locations_loading\">loading...</div>";
?>