<?php
require($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');

$db = $GLOBALS['site']->db();
$domains = $db->query('blacklist_dea', false, array('transpose' => 'domain'));
$results = array();
foreach($domains as $domain) {
	$results[$domain] = $db->query('public_users', array(sprintf("!email LIKE '%%%s'", $domain)));
}

foreach($results as $domain => $r) {
	if ($r) {
		echo "<h1>$domain</h1>";
		echo "<table>";
		$temp = $r[0];
		$keys = array_keys($temp);
		echo HTMLHelper::wrapArrayInTh($keys);
		foreach($r as $row) {
			echo HTMLHelper::wrapArrayInTr($row);
		}
		echo "</table>";
	}
}
?>